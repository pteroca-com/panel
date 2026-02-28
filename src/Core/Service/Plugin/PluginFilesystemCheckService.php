<?php

namespace App\Core\Service\Plugin;

use App\Core\Service\AbstractFilesystemCheckService;
use Symfony\Component\Filesystem\Filesystem;

class PluginFilesystemCheckService extends AbstractFilesystemCheckService
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $pluginsDirectory,
        private readonly string $tempDirectory,
        Filesystem $filesystem,
    ) {
        parent::__construct($filesystem);
    }

    protected function getRequiredPaths(): array
    {
        return [
            $this->tempDirectory                    => 'var/tmp/plugin-uploads',
            $this->pluginsDirectory                 => 'plugins/',
            $this->projectDir . '/var/cache'        => 'var/cache',
            $this->projectDir . '/var/log'          => 'var/log',
            $this->projectDir . '/public/plugins'   => 'public/plugins',
        ];
    }
}
