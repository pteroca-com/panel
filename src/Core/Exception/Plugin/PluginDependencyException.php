<?php

namespace App\Core\Exception\Plugin;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when plugin dependency validation fails.
 *
 * This exception is used when:
 * - Plugin tries to enable but required dependencies are not installed
 * - Plugin tries to enable but required dependencies are not enabled
 * - Plugin tries to enable but dependency versions are incompatible
 * - Circular dependency is detected between plugins
 * - Plugin tries to disable but other plugins depend on it (without cascade)
 *
 * Example usage:
 *
 * ```php
 * $errors = $this->dependencyResolver->validateDependencies($plugin);
 * if (!empty($errors)) {
 *     throw new PluginDependencyException(
 *         "Cannot enable plugin: " . implode("\n", $errors)
 *     );
 * }
 * ```
 */
class PluginDependencyException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
