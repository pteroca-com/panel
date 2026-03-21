<?php

namespace App\Core\Exception\License;

use RuntimeException;

class FileBlacklistedException extends RuntimeException
{
    public function __construct(
        private readonly string $reason,
        string $message = '',
    ) {
        parent::__construct($message ?: "File has been blacklisted: $reason");
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
