<?php

namespace App\Core\Event\Plugin;

use App\Core\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginFaultedEvent extends Event
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly string $reason,
    ) {}

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getPluginName(): string
    {
        return $this->plugin->getName();
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
