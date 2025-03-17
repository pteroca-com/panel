<?php

namespace App\Core\Exception;

use Exception;

class UserDoesNotHaveClientApiKeyException extends Exception
{
    private const MESSAGE = 'User does not have a Pterodactyl Client API key.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
