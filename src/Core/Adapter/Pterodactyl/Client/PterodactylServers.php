<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylServersInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylClientServer;
use App\Core\DTO\Pterodactyl\Collection;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PterodactylServers extends AbstractPterodactylClientAdapter implements PterodactylServersInterface
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getServer(string $serverId, array $include = []): PterodactylClientServer
    {
        $options = [];
        if (!empty($include)) {
            $options['query'] = ['include' => implode(',', $include)];
        }

        $response = $this->makeRequest('GET', "servers/$serverId", $options);
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylClientServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getServerUtilization(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/$serverId/utilization");
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve server utilization');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPowerSignal(string $serverId, string $signal): bool
    {
        $response = $this->makeRequest('POST', "servers/$serverId/power", [
            'json' => ['signal' => $signal]
        ]);
        return $response->getStatusCode() === 204;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendCommand(string $serverId, string $command): bool
    {
        $response = $this->makeRequest('POST', "servers/$serverId/command", [
            'json' => ['command' => $command]
        ]);
        return $response->getStatusCode() === 204;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getWebSocketToken(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/$serverId/websocket");
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve WebSocket token');
        }

        $data = $response->toArray();
        return $this->getDataFromResponse($data) ?: $data;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function reinstallServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/$serverId/settings/reinstall");
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 202) {
            $content = $response->getContent(false);
            throw new Exception(
                sprintf('Failed to reinstall server. Status: %d, Response: %s', $statusCode, $content)
            );
        }

        return true;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getServerResources(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/$serverId/resources");
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve server resources');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateServerName(string $serverId, string $name, ?string $description = null): PterodactylClientServer
    {
        $payload = ['name' => $name];
        if ($description !== null) {
            $payload['description'] = $description;
        }

        $response = $this->makeRequest('POST', "servers/$serverId/settings/rename", [
            'json' => $payload
        ]);

        if ($response->getStatusCode() === 204) {
            // API returns 204 No Content, fetch updated server data
            return $this->getServer($serverId);
        }

        throw new \RuntimeException('Unexpected status code: ' . $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateServerDockerImage(string $serverId, string $dockerImage): PterodactylClientServer
    {
        $response = $this->makeRequest('PUT', "servers/$serverId/docker-image", [
            'json' => ['docker_image' => $dockerImage]
        ]);

        if ($response->getStatusCode() === 204) {
            // API returns 204 No Content, fetch updated server data
            return $this->getServer($serverId);
        }

        throw new \RuntimeException('Unexpected status code: ' . $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateServerStartup(string $serverId, array $startupData): PterodactylClientServer
    {
        $response = $this->makeRequest('PUT', "servers/$serverId/startup", [
            'json' => $startupData
        ]);

        if ($response->getStatusCode() === 204) {
            // API returns 204 No Content, fetch updated server data
            return $this->getServer($serverId);
        }

        throw new \RuntimeException('Unexpected status code: ' . $response->getStatusCode());
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    public function updateServerStartupVariable(string $serverId, string $key, string $value): array
    {
        $response = $this->makeRequest('PUT', "servers/$serverId/startup/variable", [
            'json' => [
                'key' => $key,
                'value' => $value
            ]
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $content = $response->getContent(false);
            throw new Exception(
                sprintf('Failed to update server startup variable. Status: %d, Response: %s', $statusCode, $content)
            );
        }

        $data = $response->toArray();
        return $this->getDataFromResponse($data) ?: $data;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getServerStartup(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/$serverId/startup");
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve server startup configuration');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getServerActivity(string $serverId, array $parameters = []): Collection
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', "servers/$serverId/activity", $options);
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve server activity');
        }

        $data = $response->toArray();
        return new Collection($data['data'] ?? [], $data['meta'] ?? []);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getPermissions(): array
    {
        $response = $this->makeRequest('GET', 'permissions');
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve permissions');
        }

        return $this->getDataFromResponse($response->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getServerPermissions(string $serverId): array
    {
        $response = $this->makeRequest('GET', "servers/$serverId/permissions");
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to retrieve server permissions');
        }

        return $this->getDataFromResponse($response->toArray());
    }
}
