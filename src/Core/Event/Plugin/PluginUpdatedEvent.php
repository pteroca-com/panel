<?php

namespace App\Core\Event\Plugin;

use App\Core\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginUpdatedEvent extends Event
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly string $oldVersion,
        private readonly string $newVersion,
    ) {}

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getPluginName(): string
    {
        return $this->plugin->getName();
    }

    public function getOldVersion(): string
    {
        return $this->oldVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }
}
