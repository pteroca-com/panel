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
        exec('git stash', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to stash changes.');
            return;
        }

        $this->isStashed = true;
    }

    private function pullGitChanges(): void
    {
        exec('git pull origin main', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to pull changes.');
            $this->applyStashedChanges();
        }
    }

    private function applyStashedChanges(): void
    {
        if ($this->isStashed) {
            exec('git stash apply', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->hasError = true;
                $this->io->error('Failed to apply stashed changes.');
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
}
