<?php

namespace App\Core\Exception\Plugin;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when plugin capability validation fails.
 *
 * This exception is used when:
 * - Plugin tries to use a capability it hasn't declared in manifest
 * - Plugin declares an invalid/unknown capability
 * - There's a conflict with capability requirements
 *
 * Example usage:
 *
 * ```php
 * if (!$plugin->hasCapability('console')) {
 *     throw PluginCapabilityException::missingCapability(
 *         $plugin->getName(),
 *         'console',
 *         'register console commands'
 *     );
 * }
 * ```
 */
class PluginCapabilityException extends RuntimeException
{
    public static function missingCapability(
        string $pluginName,
        string $capability,
        string $operation,
        ?Throwable $previous = null
    ): self {
        $message = sprintf(
            "Plugin '%s' attempted to %s but does not have '%s' capability declared in manifest. " .
            "Add '%s' to the 'capabilities' array in plugin.json to enable this feature.",
            $pluginName,
            $operation,
            $capability,
            $capability
        );

        return new self($message, 0, $previous);
    }

    public static function invalidCapability(
        string $pluginName,
        string $capability,
        array $validCapabilities,
        ?Throwable $previous = null
    ): self {
        $message = sprintf(
            "Plugin '%s' declares invalid capability '%s'. Valid capabilities are: %s",
            $pluginName,
            $capability,
            implode(', ', $validCapabilities)
        );

        return new self($message, 0, $previous);
    }

    public static function capabilityConflict(
        string $pluginName,
        string $capability,
        string $reason,
        ?Throwable $previous = null
    ): self {
        $message = sprintf(
            "Plugin '%s' has a conflict with capability '%s': %s",
            $pluginName,
            $capability,
            $reason
        );

        return new self($message, 0, $previous);
    }
}
