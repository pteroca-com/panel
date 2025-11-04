<?php

namespace App\Core\Exception\Plugin;

use App\Core\Enum\PluginStateEnum;
use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        string $pluginName,
        PluginStateEnum $currentState,
        PluginStateEnum $targetState,
        string $reason = ''
    ) {
        $message = sprintf(
            "Invalid state transition for plugin '%s': cannot transition from '%s' to '%s'",
            $pluginName,
            $currentState->value,
            $targetState->value
        );

        if ($reason !== '') {
            $message .= ". Reason: $reason";
        }

        parent::__construct($message);
    }
}
