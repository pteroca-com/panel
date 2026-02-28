<?php

namespace App\Core\Service\Plugin;

use Symfony\Component\Filesystem\Filesystem;

class PluginFilesystemCheckService
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $pluginsDirectory,
        private readonly string $tempDirectory,
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * Returns list of relative paths that are not writable by the web server.
     * Empty array means all required permissions are OK.
     */
    public function getUnwritablePaths(): array
    {
        $unwritable = [];

        foreach ($this->getRequiredPaths() as $absolutePath => $relativePath) {
            if (!$this->filesystem->exists($absolutePath)) {
                try {
                    $this->filesystem->mkdir($absolutePath, 0755);
                } catch (\Exception) {
                    $unwritable[] = $relativePath;
                    continue;
                }
            }

            if (!is_writable($absolutePath)) {
                $unwritable[] = $relativePath;
            }
        }

        return $unwritable;
    }

    private function getRequiredPaths(): array
    {
        return [
            $this->tempDirectory => 'var/tmp/plugin-uploads',
            $this->pluginsDirectory => 'plugins/',
            $this->projectDir . '/var/cache' => 'var/cache',
            $this->projectDir . '/var/log' => 'var/log',
            $this->projectDir . '/public/plugins' => 'public/plugins',
        ];
    }
}
