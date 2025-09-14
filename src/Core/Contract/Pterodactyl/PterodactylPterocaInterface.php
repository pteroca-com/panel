<?php

namespace App\Core\Contract\Pterodactyl;

use App\Core\DTO\Pterodactyl\PterodactylNode;

interface PterodactylPterocaInterface
{
    public function getPterocaVersion(): string;
}