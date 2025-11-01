<?php

namespace App\Core\Enum;

enum PluginStateEnum: string
{
    case DISCOVERED = 'discovered';
    case REGISTERED = 'registered';
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
    case UPDATE_PENDING = 'update_pending';
    case FAULTED = 'faulted';

    public function getLabel(): string
    {
        return match ($this) {
            self::DISCOVERED => 'pteroca.enum.plugin_state.discovered',
            self::REGISTERED => 'pteroca.enum.plugin_state.registered',
            self::ENABLED => 'pteroca.enum.plugin_state.enabled',
            self::DISABLED => 'pteroca.enum.plugin_state.disabled',
            self::UPDATE_PENDING => 'pteroca.enum.plugin_state.update_pending',
            self::FAULTED => 'pteroca.enum.plugin_state.faulted',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::DISCOVERED => 'info',
            self::REGISTERED => 'secondary',
            self::ENABLED => 'success',
            self::DISABLED => 'warning',
            self::UPDATE_PENDING => 'primary',
            self::FAULTED => 'danger',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ENABLED;
    }

    public function canBeEnabled(): bool
    {
        return in_array($this, [self::REGISTERED, self::DISABLED], true);
    }

    public function canBeDisabled(): bool
    {
        return $this === self::ENABLED;
    }

    public function isFaulted(): bool
    {
        return $this === self::FAULTED;
    }
}
