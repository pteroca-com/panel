<?php

namespace App\Core\Exception\Plugin;

use RuntimeException;
use Throwable;

class PluginUploadException extends RuntimeException
{
    protected array $details = [];

    public function __construct(string $message, array $details = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
