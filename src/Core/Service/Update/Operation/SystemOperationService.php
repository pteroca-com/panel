<?php

namespace App\Core\Service\Update\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;

class SystemOperationService
{
    private SymfonyStyle $io;
    private array $options = [];

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function clearCache(): void
    {
        exec('php bin/console cache:clear', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to clear cache. Output: ' . implode("\n", $output));
        }

        if ($this->options['verbose'] ?? false) {
            $this->io->success('Application cache cleared successfully.');
            if (!empty($output)) {
                $this->io->text('Cache clear output: ' . implode("\n", $output));
            }
        }
    }

    public function adjustFilePermissions(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            if ($this->options['verbose'] ?? false) {
                $this->io->text('Skipping file permissions adjustment - not on Linux system.');
            }
            return;
        }

        $directoryToCheck = \dirname(__DIR__, 5);
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
                
                if ($this->options['verbose'] ?? false) {
                    $this->io->success("File permissions adjusted to {$candidate}.");
                }
                return;
            }
        }

        if ($this->options['verbose'] ?? false) {
            $this->io->warning('Could not find appropriate web server user for file permissions.');
        }
    }

    public function validateSystemRequirements(): void
    {
        $this->checkWritePermissions();
        $this->checkRequiredCommands();
    }

    public function ensureDirectoryPermissions(): void
    {
        $directoriesToCheck = [
            'var/cache',
            'var/log',
            'var/backups',
            'var/locks',
        ];

        $rootPath = \dirname(__DIR__, 5);
        
        foreach ($directoriesToCheck as $dir) {
            $fullPath = $rootPath . '/' . $dir;
            
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                    throw new \RuntimeException("Could not create directory: {$fullPath}");
                }
            }

            if (!is_writable($fullPath)) {
                throw new \RuntimeException("Directory is not writable: {$fullPath}");
            }
        }

        if ($this->options['verbose'] ?? false) {
            $this->io->success('Directory permissions validated successfully.');
        }
    }

    private function checkWritePermissions(): void
    {
        $directoryToCheck = \dirname(__DIR__, 5);
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

        if ($this->options['verbose'] ?? false) {
            $this->io->text('Write permissions verified.');
        }
    }

    private function checkRequiredCommands(): void
    {
        $requiredCommands = ['git', 'composer', 'php'];
        $missingCommands = [];

        foreach ($requiredCommands as $command) {
            exec("which {$command} 2>/dev/null", $output, $returnCode);
            if ($returnCode !== 0) {
                $missingCommands[] = $command;
            }
        }

        if (!empty($missingCommands)) {
            throw new \RuntimeException('Missing required commands: ' . implode(', ', $missingCommands));
        }

        if ($this->options['verbose'] ?? false) {
            $this->io->text('All required system commands are available.');
        }
    }

    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'os_family' => PHP_OS_FAMILY,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'environment' => $_ENV['APP_ENV'] ?? 'prod',
        ];
    }
}
