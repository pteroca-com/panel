<?php

namespace App\Core\Service\Update;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class BackupService
{
    private const BACKUP_DIR = 'var/backups';
    private const BACKUP_RETENTION_DAYS = 7;

    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function createDatabaseBackup(int $retentionDays = self::BACKUP_RETENTION_DAYS): string
    {
        $backupPath = $this->generateBackupPath();
        
        $backupDir = dirname($backupPath);
        if (!$this->filesystem->exists($backupDir)) {
            $this->filesystem->mkdir($backupDir, 0755);
        }

        $this->performBackup($backupPath);
        $this->cleanupOldBackups($retentionDays);

        return $backupPath;
    }

    public function restoreDatabaseBackup(string $backupPath): void
    {
        if (!$this->filesystem->exists($backupPath)) {
            throw new \RuntimeException("Backup file not found: {$backupPath}");
        }

        $databaseUrl = $_ENV['DATABASE_URL'] ?? null;
        if (!$databaseUrl) {
            throw new \RuntimeException('DATABASE_URL environment variable not set');
        }

        $parsedUrl = parse_url($databaseUrl);
        $dbName = ltrim($parsedUrl['path'], '/');

        $command = sprintf(
            'mysql -h%s -P%d -u%s -p%s %s < %s',
            $parsedUrl['host'],
            $parsedUrl['port'] ?? 3306,
            $parsedUrl['user'],
            $parsedUrl['pass'],
            $dbName,
            escapeshellarg($backupPath)
        );

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'Database restore failed: %s',
                $process->getErrorOutput()
            ));
        }
    }

    public function validateBackup(string $backupPath): bool
    {
        if (!$this->filesystem->exists($backupPath)) {
            return false;
        }

        $fileSize = filesize($backupPath);
        if ($fileSize < 50) {
            return false;
        }

        $handle = fopen($backupPath, 'r');
        if ($handle) {
            $content = fread($handle, 2048);
            fclose($handle);
            
            return stripos($content, 'CREATE TABLE') !== false || 
                   stripos($content, 'INSERT INTO') !== false ||
                   stripos($content, 'mysqldump') !== false ||
                   stripos($content, 'DROP TABLE') !== false ||
                   stripos($content, 'CREATE DATABASE') !== false ||
                   stripos($content, 'USE ') !== false ||
                   stripos($content, '-- MySQL dump') !== false ||
                   (stripos($content, 'SET') !== false && stripos($content, 'SQL_MODE') !== false);
        }

        return false;
    }

    public function getBackupSize(string $backupPath): int
    {
        if (!$this->filesystem->exists($backupPath)) {
            return 0;
        }

        return filesize($backupPath);
    }

    public function listBackups(): array
    {
        $backupDir = $this->getBackupDir();
        
        if (!$this->filesystem->exists($backupDir)) {
            return [];
        }

        $backups = [];
        $files = scandir($backupDir);
        
        foreach ($files as $file) {
            if (preg_match('/^db_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql$/', $file, $matches)) {
                $fullPath = $backupDir . '/' . $file;
                $backups[] = [
                    'filename' => $file,
                    'path' => $fullPath,
                    'timestamp' => $matches[1],
                    'size' => filesize($fullPath),
                    'created' => filemtime($fullPath)
                ];
            }
        }

        usort($backups, fn($a, $b) => $b['created'] - $a['created']);

        return $backups;
    }

    private function generateBackupPath(): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        return $this->getBackupDir() . "/db_{$timestamp}.sql";
    }

    private function getBackupDir(): string
    {
        return dirname(__DIR__, 4) . '/' . self::BACKUP_DIR;
    }

    private function performBackup(string $backupPath): void
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? null;
        if (!$databaseUrl) {
            throw new \RuntimeException('DATABASE_URL environment variable not set');
        }

        $parsedUrl = parse_url($databaseUrl);
        $dbName = ltrim($parsedUrl['path'], '/');

        $command = sprintf(
            'mysqldump -h%s -P%d -u%s -p%s --single-transaction --routines --triggers --add-drop-table --quick --lock-tables=false %s',
            $parsedUrl['host'],
            $parsedUrl['port'] ?? 3306,
            $parsedUrl['user'],
            $parsedUrl['pass'],
            $dbName
        );

        $process = new Process(explode(' ', $command));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'Database backup failed: %s. Command: %s',
                $process->getErrorOutput() ?: $process->getOutput(),
                $command
            ));
        }

        $backupContent = $process->getOutput();
        if (empty($backupContent)) {
            throw new \RuntimeException('Database backup produced no output');
        }

        file_put_contents($backupPath, $backupContent);

        if (!$this->validateBackup($backupPath)) {
            $fileSize = file_exists($backupPath) ? filesize($backupPath) : 0;
            $preview = file_exists($backupPath) ? substr(file_get_contents($backupPath), 0, 200) : 'File not found';
            
            throw new \RuntimeException(sprintf(
                'Backup validation failed - backup file is invalid or empty. File size: %d bytes. Preview: %s',
                $fileSize,
                $preview
            ));
        }
    }

    private function cleanupOldBackups(int $retentionDays): void
    {
        $backups = $this->listBackups();
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);

        foreach ($backups as $backup) {
            if ($backup['created'] < $cutoffTime) {
                try {
                    $this->filesystem->remove($backup['path']);
                } catch (\Exception $e) {
                    error_log("Failed to remove old backup {$backup['path']}: " . $e->getMessage());
                }
            }
        }
    }
}
