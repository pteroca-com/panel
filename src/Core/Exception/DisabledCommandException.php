<?php

namespace App\Core\Exception;

use Exception;

class DisabledCommandException extends Exception
{
    private const MESSAGE = 'Command is disabled';

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::MESSAGE);
    }
}
