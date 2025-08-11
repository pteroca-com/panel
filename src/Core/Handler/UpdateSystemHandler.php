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
        $label = 'pteroca-auto-update-' . date('Ymd-His');
        exec(sprintf('git stash push -u -m %s', escapeshellarg($label)), $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to stash changes.');
            return;
        }

        $this->isStashed = true;
    }

    private function pullGitChanges(): void
    {
        // explains itself cuz idk whats wrong imma fetch origin first
        exec('git fetch origin main', $outputFetch, $codeFetch);
        if ($codeFetch !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to fetch changes from origin.');
            $this->applyStashedChanges();
            return;
        }

        exec('git merge --ff-only origin/main', $outputFf, $codeFf);
        if ($codeFf === 0) {
            return; 
        }

        $this->io->warning('Fast-forward not possible. Attempting a no-ff merge...');
        exec('git merge --no-ff --no-edit origin/main', $outputMerge, $codeMerge);
        if ($codeMerge !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to merge changes. Resolve merge conflicts and try again.');
        }
    }

    private function applyStashedChanges(): void
    {
        if ($this->isStashed) {
            exec('git stash pop --index', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->io->warning('Could not automatically re-apply stashed changes. They were kept in the stash. Run "git stash list" and "git stash pop" manually after resolving any file conflicts.');
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
        exec('php bin/console cache:clear', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to clear cache.');
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
}
