<?php

namespace App\Core\Handler;

use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSystemHandler implements HandlerInterface
{
    private bool $hasError = false;

    private bool $isStashed = false;

    private ?string $stashRef = null;

    private SymfonyStyle $io;

    public function handle(): void
    {
        $this->checkPermissions();
        $this->checkIfGitIsInstalled();
        $this->checkIfComposerIsInstalled();
        $this->showWarningMessage();

        $this->ensureNoUnmergedFiles();
        if ($this->hasError) { return; }

        $this->stashGitChanges();
        if ($this->hasError) { return; }

        $this->pullGitChanges();
        if ($this->hasError) { return; }

        $this->applyStashedChanges();
        if ($this->hasError) { return; }

        $this->composerInstall();
        if ($this->hasError) { return; }

        $this->updateDatabase();
        if ($this->hasError) { return; }

        $this->clearCache();
        if ($this->hasError) { return; }

        $this->clearLogs();
        if ($this->hasError) { return; }

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
        $statusOutput = [];
        $statusCode = 0;
        exec('git status --porcelain', $statusOutput, $statusCode);
        $hasChanges = $statusCode === 0 && !empty(array_filter($statusOutput));

        if (!$hasChanges) {
            $this->isStashed = false;
            $this->stashRef = null;
            return;
        }

        $output = [];
        $returnCode = 0;
        $label = 'pteroca-update-' . date('Ymd-His');
        exec(sprintf('git stash push -u -m %s', escapeshellarg($label)), $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to stash changes.');
            return;
        }

        $stashCheck = [];
        $stashCode = 0;
        exec('git rev-parse -q --verify refs/stash', $stashCheck, $stashCode);
        $this->isStashed = ($stashCode === 0);

        if ($this->isStashed) {
            $list = [];
            $listCode = 0;
            exec('git stash list --pretty=format:%gd:%s', $list, $listCode);
            if ($listCode === 0 && !empty($list)) {
                foreach ($list as $line) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2 && str_contains($parts[1], $label)) {
                        $this->stashRef = trim($parts[0]);
                        break;
                    }
                }
            }
        }
    }

    private function pullGitChanges(): void
    {
        exec('git fetch origin', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to fetch changes from remote.');
            $this->applyStashedChanges();
            return;
        }

        exec('git pull --ff-only origin main', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to pull changes.');
            $this->applyStashedChanges();
            return;
        }
    }

    private function applyStashedChanges(): void
    {
        if (!$this->isStashed) {
            return;
        }

        $stashCheck = [];
        $stashCode = 0;
        exec('git rev-parse -q --verify refs/stash', $stashCheck, $stashCode);
        if ($stashCode !== 0) {
            $this->isStashed = false;
            $this->stashRef = null;
            return;
        }

        $output = [];
        $returnCode = 0;
        if ($this->stashRef) {
            exec(sprintf('git stash pop %s', escapeshellarg($this->stashRef)), $output, $returnCode);
        } else {
            exec('git stash pop', $output, $returnCode);
        }
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to apply stashed changes.');
            return;
        }

        $this->isStashed = false;
        $this->stashRef = null;
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
        exec('php bin/console cache:clear --env=prod', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to clear cache.');
            return;
        }
        exec('php bin/console cache:warmup --env=prod', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to warmup cache.');
        }
    }

    private function clearLogs(): void
    {
        $root = \dirname(__DIR__, 3);
        $logDirs = [];
        if (is_dir($root . '/var/log')) { $logDirs[] = $root . '/var/log'; }
        if (is_dir($root . '/var/logs')) { $logDirs[] = $root . '/var/logs'; }

        foreach ($logDirs as $dir) {
            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $item) {
                    if ($item->isFile() || $item->isLink()) {
                        @unlink($item->getPathname());
                    } elseif ($item->isDir()) {
                        // Only remove nested empty directories under log dir, keep the root log dir
                        @rmdir($item->getPathname());
                    }
                }
            } catch (\Throwable $e) {
                $this->hasError = true;
                $this->io->error('Failed to clear logs.');
                return;
            }
        }
    }

    private function adjustFilePermissions(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return;
        }

        $root = \dirname(__DIR__, 3);
        $paths = [];
        if (is_dir($root . '/var')) { $paths[] = $root . '/var'; }
        if (is_dir($root . '/public')) { $paths[] = $root . '/public'; }

        if (empty($paths)) { return; }

        $candidateOwners = [
            'www-data:www-data',
            'nginx:nginx',
            'apache:apache',
        ];

        foreach ($candidateOwners as $candidate) {
            [$user, $group] = explode(':', $candidate);

            $exitCode = 0;
            $out = [];
            exec(sprintf('id -u %s 2>/dev/null', escapeshellarg($user)), $out, $exitCode);
            if ($exitCode === 0) {
                foreach ($paths as $p) {
                    $pEsc = escapeshellarg($p);
                    exec(sprintf('chown -R %s %s', escapeshellarg($candidate), $pEsc));
                    exec(sprintf('find %s -type d -exec chmod 775 {} \\;', $pEsc));
                    exec(sprintf('find %s -type f -exec chmod 664 {} \\;', $pEsc));
                }
                return;
            }
        }
    }

    private function ensureNoUnmergedFiles(): void
    {
        $out = [];
        $code = 0;
        exec('git diff --name-only --diff-filter=U', $out, $code);
        if ($code === 0 && !empty(array_filter($out))) {
            $this->hasError = true;
            $this->io->error('Unmerged files detected. Please resolve merge conflicts and commit before running the updater.');
        }
    }
}
