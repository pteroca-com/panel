<?php

namespace App\Core\DTO\Pterodactyl;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylNode extends Resource
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
