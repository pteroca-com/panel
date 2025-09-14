<?php

namespace App\Core\Adapter\Pterodactyl;

use App\Core\Contract\Pterodactyl\PterodactylServersInterface;
use App\Core\DTO\Pterodactyl\PterodactylServer;

class PterodactylServers extends AbstractPterodactylAdapter implements PterodactylServersInterface
{

    public function all(array $parameters = []): array
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', 'servers', $options);
        $data = $this->validateServerResponse($response, 200);

        $servers = [];
        foreach ($data['data'] as $serverData) {
            $servers[] = new PterodactylServer($serverData['attributes']);
        }

        return $servers;
    }

    public function getServer(string $serverId, array $include = []): PterodactylServer
    {
        $options = [];
        if (!empty($include)) {
            $options['query'] = ['include' => implode(',', $include)];
        }

        $response = $this->makeRequest('GET', "servers/{$serverId}", $options);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylServer($data['attributes']);
    }

    public function suspendServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/suspend");
        return $response->getStatusCode() === 204;
    }

    public function unsuspendServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/unsuspend");
        return $response->getStatusCode() === 204;
    }

    public function updateServerDetails(string $serverId, array $details): bool
    {
        $response = $this->makeRequest('PATCH', "servers/{$serverId}/details", ['json' => $details]);
        return in_array($response->getStatusCode(), [200, 204]);
    }

    public function updateServerBuild(string $serverId, array $buildDetails): bool
    {
        $response = $this->makeRequest('PATCH', "servers/{$serverId}/build", ['json' => $buildDetails]);
        return in_array($response->getStatusCode(), [200, 204]);
    }

    public function updateServerStartup(string $serverId, array $startupDetails): bool
    {
        $response = $this->makeRequest('PATCH', "servers/{$serverId}/startup", ['json' => $startupDetails]);
        return in_array($response->getStatusCode(), [200, 204]);
    }

    public function deleteServer(string $serverId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/{$serverId}");
        return $response->getStatusCode() === 204;
    }

    public function createServer(array $details): PterodactylServer
    {
        $response = $this->makeRequest('POST', 'servers', ['json' => $details]);
        $data = $this->validateServerResponse($response, 201);
        
        return new PterodactylServer($data['attributes']);
    }

    public function paginate(int $page = 1, array $query = []): array
    {
        $options = ['query' => array_merge(['page' => $page], $query)];
        
        $response = $this->makeRequest('GET', 'servers', $options);
        $data = $this->validateServerResponse($response, 200);

        $servers = [];
        foreach ($data['data'] as $serverData) {
            $servers[] = new PterodactylServer($serverData['attributes']);
        }

        return $servers;
    }

    public function getServerByExternalId(string $externalId, array $query = []): PterodactylServer
    {
        $options = [];
        if (!empty($query)) {
            $options['query'] = $query;
        }

        $response = $this->makeRequest('GET', "servers/external/{$externalId}", $options);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylServer($data['attributes']);
    }

    public function reinstallServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/reinstall");
        return $response->getStatusCode() === 202;
    }

    public function forceDeleteServer(string $serverId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/{$serverId}/force");
        return $response->getStatusCode() === 204;
    }

}
