<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylServersInterface;
use App\Core\DTO\Pterodactyl\Application\PterodactylServer;
use App\Core\DTO\Pterodactyl\Collection;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PterodactylServers extends AbstractPterodactylApplicationAdapter implements PterodactylServersInterface
{

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function all(array $parameters = []): Collection
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

        return new Collection($servers, $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getServer(string $serverId, array $include = []): PterodactylServer
    {
        $options = [];
        if (!empty($include)) {
            $options['query'] = ['include' => implode(',', $include)];
        }

        $response = $this->makeRequest('GET', "servers/$serverId", $options);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function suspendServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/$serverId/suspend");
        return $response->getStatusCode() === 204;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function unsuspendServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/$serverId/unsuspend");
        return $response->getStatusCode() === 204;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateServerDetails(string $serverId, array $details): PterodactylServer
    {
        $response = $this->makeRequest('PATCH', "servers/$serverId/details", ['json' => $details]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateServerBuild(string $serverId, array $buildDetails): PterodactylServer
    {
        $response = $this->makeRequest('PATCH', "servers/$serverId/build", ['json' => $buildDetails]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateServerStartup(string $serverId, array $startupDetails): PterodactylServer
    {
        $response = $this->makeRequest('PATCH', "servers/$serverId/startup", ['json' => $startupDetails]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function deleteServer(string $serverId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/$serverId");
        return $response->getStatusCode() === 204;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function createServer(array $details): PterodactylServer
    {
        $response = $this->makeRequest('POST', 'servers', ['json' => $details]);
        $data = $this->validateServerResponse($response, 201);
        
        return new PterodactylServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function paginate(int $page = 1, array $query = []): Collection
    {
        $options = ['query' => array_merge(['page' => $page], $query)];
        
        $response = $this->makeRequest('GET', 'servers', $options);
        $data = $this->validateServerResponse($response, 200);

        $servers = [];
        foreach ($data['data'] as $serverData) {
            $servers[] = new PterodactylServer($serverData['attributes']);
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
    public function getServerByExternalId(string $externalId, array $query = []): PterodactylServer
    {
        $options = [];
        if (!empty($query)) {
            $options['query'] = $query;
        }

        $response = $this->makeRequest('GET', "servers/external/$externalId", $options);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylServer($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function reinstallServer(string $serverId): bool
    {
        $response = $this->makeRequest('POST', "servers/$serverId/reinstall");
        return $response->getStatusCode() === 202;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function forceDeleteServer(string $serverId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/$serverId/force");
        return $response->getStatusCode() === 204;
    }

}
