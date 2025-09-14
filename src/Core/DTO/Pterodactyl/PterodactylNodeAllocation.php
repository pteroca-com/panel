<?php

namespace App\Core\DTO\Pterodactyl;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylNodeAllocation extends Resource
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
