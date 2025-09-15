<?php

declare(strict_types=1);

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylDatabasesInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylDatabase;
use App\Core\DTO\Pterodactyl\Collection;

final class PterodactylDatabases extends AbstractPterodactylClientAdapter implements PterodactylDatabasesInterface
{
    public function getDatabases(string $serverId, array $params = []): Collection
    {
        $options = [];
        if (!empty($params)) {
            $options['query'] = $params;
        }
        
        $response = $this->makeRequest('GET', "servers/{$serverId}/databases", $options);
        $data = $this->validateListResponse($response, 200);

        $items = array_map(
            fn(array $database): PterodactylDatabase => new PterodactylDatabase($database),
            $data['data'] ?? []
        );

        return new Collection($items, $this->getMetaFromResponse($data));
    }

    public function createDatabase(string $serverId, string $database, string $remote): PterodactylDatabase
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/databases", [
            'json' => [
                'database' => $database,
                'remote' => $remote,
            ],
        ]);

        $data = $this->validateClientResponse($response, 201);

        return new PterodactylDatabase($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function rotatePassword(string $serverId, string $databaseId): PterodactylDatabase
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/databases/{$databaseId}/rotate-password");
        $data = $this->validateClientResponse($response, 200);

        return new PterodactylDatabase($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function deleteDatabase(string $serverId, string $databaseId): void
    {
        $response = $this->makeRequest('DELETE', "servers/{$serverId}/databases/{$databaseId}");
        
        if ($response->getStatusCode() !== 204) {
            throw new \Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }
}
