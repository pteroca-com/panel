<?php

namespace App\Core\Exception\Email;

use Exception;

class ServerDetailsNotAvailableException extends Exception
{
    public function __construct(string $message = 'Server details are not available', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
