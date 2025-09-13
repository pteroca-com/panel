<?php

namespace App\Core\Exception;

use Exception;

class PterodactylUserNotFoundException extends Exception
{
    public function __construct(string $message = 'User not found in Pterodactyl', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
