<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylAdapterInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylLocationsInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylNestEggsInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylNestsInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylNodeAllocationsInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylNodesInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylPterocaInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylServersInterface;
use App\Core\Contract\Pterodactyl\Application\PterodactylUsersInterface;
use App\Core\DTO\Pterodactyl\Credentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PterodactylAdapter implements PterodactylAdapterInterface
{
    private PterodactylServers $servers;
    private PterodactylUsers $users;
    private PterodactylNodes $nodes;
    private PterodactylNodeAllocations $nodeAllocations;
    private PterodactylLocations $locations;
    private PterodactylNests $nests;
    private PterodactylNestEggs $nestEggs;
    private PterodactylPteroca $pteroca;
    private Credentials $apiCredentials;

    public function __construct(
        public readonly HttpClientInterface $httpClient,
    ) {
    }

    public function setCredentials(Credentials $credentials): void
    {
        $this->apiCredentials = $credentials;
    }

    public function servers(): PterodactylServersInterface
    {
        if (!isset($this->servers)) {
            $this->servers = new PterodactylServers($this->httpClient, $this->apiCredentials);
        }

        return $this->servers;
    }

    public function users(): PterodactylUsersInterface
    {
        if (!isset($this->users)) {
            $this->users = new PterodactylUsers($this->httpClient, $this->apiCredentials);
        }

        return $this->users;
    }

    public function nodes(): PterodactylNodesInterface
    {
        if (!isset($this->nodes)) {
            $this->nodes = new PterodactylNodes($this->httpClient, $this->apiCredentials);
        }

        return $this->nodes;
    }

    public function nodeAllocations(): PterodactylNodeAllocationsInterface
    {
        if (!isset($this->nodeAllocations)) {
            $this->nodeAllocations = new PterodactylNodeAllocations($this->httpClient, $this->apiCredentials);
        }

        return $this->nodeAllocations;
    }

    public function locations(): PterodactylLocationsInterface
    {
        if (!isset($this->locations)) {
            $this->locations = new PterodactylLocations($this->httpClient, $this->apiCredentials);
        }

        return $this->locations;
    }

    public function nests(): PterodactylNestsInterface
    {
        if (!isset($this->nests)) {
            $this->nests = new PterodactylNests($this->httpClient, $this->apiCredentials);
        }

        return $this->nests;
    }

    public function nestEggs(): PterodactylNestEggsInterface
    {
        if (!isset($this->nestEggs)) {
            $this->nestEggs = new PterodactylNestEggs($this->httpClient, $this->apiCredentials);
        }

        return $this->nestEggs;
    }

    public function pteroca(): PterodactylPterocaInterface
    {
        if (!isset($this->pteroca)) {
            $this->pteroca = new PterodactylPteroca($this->httpClient, $this->apiCredentials);
        }

        return $this->pteroca;
    }
}
