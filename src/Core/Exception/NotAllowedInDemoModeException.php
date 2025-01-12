<?php

namespace App\Core\Exception;

use Exception;

class NotAllowedInDemoModeException extends Exception
{
    private const MESSAGE = 'Action not allowed in demo mode';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
