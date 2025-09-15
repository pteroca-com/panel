<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylPterocaInterface;

class PterodactylPteroca extends AbstractPterodactylApplicationAdapter implements PterodactylPterocaInterface
{
    public function getVersion(): array
    {
        $response = $this->makeRequest('GET', 'pteroca/version');
        return $this->validateServerResponse($response, 200);
    }
}
