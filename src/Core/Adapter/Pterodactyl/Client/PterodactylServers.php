<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylServersInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylClientServer;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylServers extends AbstractPterodactylClientAdapter implements PterodactylServersInterface
{
    public function getServers(array $parameters = []): Collection
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', 'servers', $options);
        $data = $this->validateListResponse($response, 200);

        $servers = [];
        foreach ($data['data'] as $serverData) {
            $servers[] = new PterodactylClientServer($serverData['attributes']);
        }

        return new Collection($servers, $this->getMetaFromResponse($data));
    }

    public function getServer(string $serverId, array $include = []): PterodactylClientServer
    {
        $options = [];
        if (!empty($include)) {
            $options['query'] = ['include' => implode(',', $include)];
        }

        $response = $this->makeRequest('GET', "servers/{$serverId}", $options);
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylClientServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function getServerUtilization(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/utilization");
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve server utilization');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    public function sendPowerSignal(string $serverId, string $signal): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/power", [
            'json' => ['signal' => $signal]
        ]);
        return $response->getStatusCode() === 204;
    }

    public function sendCommand(string $serverId, string $command): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/command", [
            'json' => ['command' => $command]
        ]);
        return $response->getStatusCode() === 204;
    }

    public function getWebSocketToken(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/websocket");
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve WebSocket token');
        }

        $data = $response->toArray();
        return $this->getDataFromResponse($data) ?: $data;
    }

    public function reinstallServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/reinstall");
        return $response->getStatusCode() === 202;
    }

    public function getServerResources(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/resources");
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve server resources');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    public function updateServerName(string $serverId, string $name, ?string $description = null): bool
    {
        $payload = ['name' => $name];
        if ($description !== null) {
            $payload['description'] = $description;
        }

        $response = $this->makeRequest('POST', "servers/{$serverId}/settings/rename", [
            'json' => $payload
        ]);
        return $response->getStatusCode() === 204;
    }

    public function updateServerDockerImage(string $serverId, string $dockerImage): bool
    {
        $response = $this->makeRequest('PUT', "servers/{$serverId}/docker-image", [
            'json' => ['docker_image' => $dockerImage]
        ]);
        return $response->getStatusCode() === 204;
    }

    public function updateServerStartup(string $serverId, array $startupData): bool
    {
        $response = $this->makeRequest('PUT', "servers/{$serverId}/startup", [
            'json' => $startupData
        ]);
        return $response->getStatusCode() === 204;
    }

    public function updateServerStartupVariable(string $serverId, string $key, string $value): array
    {
        $response = $this->makeRequest('PUT', "servers/{$serverId}/startup/variable", [
            'json' => [
                'key' => $key,
                'value' => $value
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to update server startup variable');
        }

        $data = $response->toArray();
        return $this->getDataFromResponse($data) ?: $data;
    }

    public function getServerStartup(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/startup");
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve server startup configuration');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    public function getServerActivity(string $serverId, array $parameters = []): Collection
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', "servers/{$serverId}/activity", $options);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve server activity');
        }

        $data = $response->toArray();
        return new Collection($data['data'] ?? [], $data['meta'] ?? []);
    }

    public function getPermissions(): array
    {
        $response = $this->makeRequest('GET', 'permissions');
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve permissions');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    public function getServerPermissions(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/permissions");
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve server permissions');
        }

        return $this->getDataFromResponse($response->toArray());
    }
}
