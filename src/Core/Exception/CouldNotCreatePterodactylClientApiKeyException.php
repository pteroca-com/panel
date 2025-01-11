<?php

namespace App\Core\Exception;

use Exception;

class CouldNotCreatePterodactylClientApiKeyException extends Exception
{
    private const MESSAGE = 'Could not create Pterodactyl Client API Key. Is the PteroCA Extension for Pterodactyl installed?';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
