<?php

namespace App\Core\Service\Update;

use Symfony\Component\Filesystem\Filesystem;

class UpdateLockManager
{
    private const LOCK_FILE = 'var/locks/update.lock';
    private const MAX_LOCK_AGE = 3600; // 1 hour

    private Filesystem $filesystem;
    private bool $lockAcquired = false;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function acquireLock(): void
    {
        $lockPath = $this->getLockFilePath();
        
        if ($this->filesystem->exists($lockPath)) {
            $lockData = $this->readLockFile($lockPath);
            
            if ($lockData && $this->isLockValid($lockData)) {
                throw new \RuntimeException(sprintf(
                    'Update already in progress (PID: %d, started: %s)',
                    $lockData['pid'],
                    date('Y-m-d H:i:s', $lockData['timestamp'])
                ));
            }
            
            $this->filesystem->remove($lockPath);
        }

        $lockDir = dirname($lockPath);
        if (!$this->filesystem->exists($lockDir)) {
            $this->filesystem->mkdir($lockDir);
        }

        $lockData = [
            'pid' => getmypid(),
            'timestamp' => time(),
            'hostname' => gethostname()
        ];

        $this->filesystem->dumpFile($lockPath, json_encode($lockData, JSON_PRETTY_PRINT));
        $this->lockAcquired = true;
    }

    public function releaseLock(): void
    {
        if (!$this->lockAcquired) {
            return;
        }

        $lockPath = $this->getLockFilePath();
        if ($this->filesystem->exists($lockPath)) {
            $this->filesystem->remove($lockPath);
        }
        
        $this->lockAcquired = false;
    }

    public function isLocked(): bool
    {
        $lockPath = $this->getLockFilePath();
        
        if (!$this->filesystem->exists($lockPath)) {
            return false;
        }

        $lockData = $this->readLockFile($lockPath);
        return $lockData && $this->isLockValid($lockData);
    }

    public function __destruct()
    {
        $this->releaseLock();
    }

    private function getLockFilePath(): string
    {
        return dirname(__DIR__, 4) . '/' . self::LOCK_FILE;
    }

    private function readLockFile(string $path): ?array
    {
        try {
            $content = file_get_contents($path);
            return json_decode($content, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isLockValid(array $lockData): bool
    {
        if (isset($lockData['pid']) && !$this->isProcessRunning((int)$lockData['pid'])) {
            return false;
        }

        if (isset($lockData['timestamp'])) {
            return (time() - $lockData['timestamp']) < self::MAX_LOCK_AGE;
        }

        return false;
    }

    private function isProcessRunning(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
            return $output && strpos($output, (string)$pid) !== false;
        }

        return file_exists("/proc/$pid");
    }
}
