<?php

namespace App\Core\Exception;

use Exception;

class PterodactylAccountEmailAlreadyExists extends Exception
{
    public const MESSAGE = 'Account with this email already exists in Pterodactyl. Please use another email.';

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::MESSAGE);
    }
}
