<?php

namespace App\Core\Contract\Pterodactyl\Application;

interface PterodactylPterocaInterface
{
    /**
     * Get PteroCA Plugin version
     *
     * @return array
     */
    public function getVersion(): array;
}
