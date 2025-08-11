<?php

namespace App\Core\Handler;

use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSystemHandler implements HandlerInterface
{
    private bool $hasError = false;

    private bool $isStashed = false;

    private SymfonyStyle $io;

    public function handle(): void
    {
        $this->checkPermissions();
        $this->checkIfGitIsInstalled();
        $this->checkIfComposerIsInstalled();
        $this->assertNoUnmergedFiles();
        $this->showWarningMessage();

        $this->stashGitChanges();
        $this->pullGitChanges();
        $this->applyStashedChanges();
        $this->composerInstall();
        $this->updateDatabase();
        $this->clearCache();
        $this->adjustFilePermissions();
    }

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;

        return $this;
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function getCurrentVersion(): string
    {
        exec('git describe --tags', $output, $returnCode);
        if ($returnCode !== 0) {
            return 'N/A';
        }

        return $output[0];
    }

    private function showWarningMessage(): void
    {
        $this->io->warning('This command will update the PteroCA system. Do not hit CTRL+C during the update process.');
        if (!$this->io->confirm('Do you want to continue?')) {
            throw new \RuntimeException('Update process aborted.');
        }
    }

    private function checkIfGitIsInstalled(): void
    {
        exec('git --version', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Git is not installed.');
        }
    }

    private function checkIfComposerIsInstalled(): void
    {
        exec('composer --version', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Composer is not installed.');
        }
    }

    private function checkPermissions(): void
    {
        $directoryToCheck = \dirname(__DIR__, 3);
        $testFile = $directoryToCheck . DIRECTORY_SEPARATOR . 'permission_test_' . uniqid() . '.tmp';

        $fp = @fopen($testFile, 'w');
        if ($fp === false) {
            throw new \RuntimeException(sprintf(
                'You do not have write permissions to the directory "%s". Run the command with sudo/as root or adjust permissions.',
                $directoryToCheck
            ));
        }

        fclose($fp);
        @unlink($testFile);
    }

    private function stashGitChanges(): void
    {
        // Stash only when there are local changes
        exec('git diff --quiet || echo DIRTY', $output);
        $hasLocalChanges = \in_array('DIRTY', $output, true);
        $output = [];

        if ($hasLocalChanges) {
            $this->io->writeln('Stashing local changes...');
            exec('git stash push -u -m "pteroca-update-'.date('YmdHis').'"', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->hasError = true;
                $this->io->error('Failed to stash changes.');
                return;
            }
            $this->isStashed = true;
        } else {
            $this->isStashed = false;
        }
    }

    private function pullGitChanges(): void
    {
        $this->io->writeln('Fetching latest changes from origin/main...');
        exec('git fetch origin main', $fetchOutput, $fetchCode);
        if ($fetchCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to fetch changes from origin/main.');
            return;
        }

        // Try fast-forward only merge first
        $this->io->writeln('Attempting fast-forward merge...');
        exec('git merge --ff-only origin/main', $ffOutput, $ffCode);
        if ($ffCode === 0) {
            $this->io->success('Repository updated (fast-forward).');
            return;
        }

        // Fall back to a normal merge without fast-forward
        $this->io->writeln('Fast-forward not possible. Attempting a no-ff merge...');
        exec('git merge --no-ff origin/main', $mergeOutput, $mergeCode);
        if ($mergeCode !== 0) {
            $this->hasError = true;
            $this->io->error("Merge failed. Please resolve conflicts manually and re-run the update.\nHint: git status, fix conflicts, then git add . && git commit");
        } else {
            $this->io->success('Repository updated via merge.');
        }
    }

    private function applyStashedChanges(): void
    {
        if ($this->isStashed) {
            $this->io->writeln('Restoring stashed changes...');
            exec('git stash pop', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->hasError = true;
                $this->io->warning('Could not automatically re-apply stashed changes. You may need to resolve conflicts and apply them manually (stash kept).');
                return;
            }
            $this->isStashed = false;
        }
    }

    private function composerInstall(): void
    {
        $isDev = strtolower($_ENV['APP_ENV'] ?? '') === 'dev';
        $composerCommand = sprintf(
            'composer install %s --optimize-autoloader --no-interaction',
            $isDev ? '' : '--no-dev',
        );

        exec($composerCommand, $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to install composer dependencies.');
        }
    }

    private function updateDatabase(): void
    {
        exec('php bin/console doctrine:migrations:migrate --no-interaction', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to update database.');
        }
    }

    private function clearCache(): void
    {
        $this->io->writeln('Clearing application cache...');
        exec('php bin/console cache:clear --no-warmup', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to clear cache.');
            return;
        }
        
        $this->io->writeln('Warming up cache...');
        exec('php bin/console cache:warmup', $warmupOutput, $warmupCode);
        if ($warmupCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to warm up cache.');
        }
    }

    private function adjustFilePermissions(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return;
        }

        $directoryToCheck = \dirname(__DIR__, 3);
        $directoryToCheck = escapeshellarg($directoryToCheck);
        $candidateOwners = [
            'www-data:www-data',
            'nginx:nginx',
            'apache:apache',
        ];

        foreach ($candidateOwners as $candidate) {
            [$user, $group] = explode(':', $candidate);

            $exitCode = 0;
            $output = [];
            exec(sprintf('id -u %s 2>/dev/null', escapeshellarg($user)), $output, $exitCode);

            if ($exitCode === 0) {
                exec(sprintf('chown -R %s %s', escapeshellarg($candidate), $directoryToCheck));
                return;
            }
        }
    }

    private function assertNoUnmergedFiles(): void
    {
        // Detect unresolved merge conflicts; abort early with guidance
        $output = [];
        $exit = 0;
        exec('git ls-files -u | wc -l', $output, $exit);
        if ($exit === 0) {
            $count = (int)trim($output[0] ?? '0');
            if ($count > 0) {
                $this->hasError = true;
                $this->io->error("Unmerged files detected in the repository. Please resolve merge conflicts before running the update.\nHint: git status, fix conflicts, git add ., git commit");
                throw new \RuntimeException('Aborting update due to unmerged files.');
            }
        }
    }
}
