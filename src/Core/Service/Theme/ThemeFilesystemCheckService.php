<?php

namespace App\Core\Service\Theme;

use App\Core\Service\AbstractFilesystemCheckService;
use Symfony\Component\Filesystem\Filesystem;

class ThemeFilesystemCheckService extends AbstractFilesystemCheckService
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $themesDirectory,
        private readonly string $tempDirectory,
        Filesystem $filesystem,
    ) {
        parent::__construct($filesystem);
    }

    protected function getRequiredPaths(): array
    {
        return [
            $this->tempDirectory                       => 'var/tmp',
            $this->themesDirectory                     => 'themes/',
            $this->projectDir . '/var/cache'           => 'var/cache',
            $this->projectDir . '/var/log'             => 'var/log',
            $this->projectDir . '/public/assets/theme' => 'public/assets/theme',
        ];
    }
}
