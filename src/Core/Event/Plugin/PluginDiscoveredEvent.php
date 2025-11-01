<?php

namespace App\Core\Event\Plugin;

use App\Core\DTO\PluginManifestDTO;
use Symfony\Contracts\EventDispatcher\Event;

class PluginDiscoveredEvent extends Event
{
    public function __construct(
        private readonly string $pluginPath,
        private readonly PluginManifestDTO $manifest,
    ) {}

    public function getPluginPath(): string
    {
        return $this->pluginPath;
    }

    public function getManifest(): PluginManifestDTO
    {
        return $this->manifest;
    }

    public function getPluginName(): string
    {
        return $this->manifest->name;
    }
}
