<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylUsersInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylSubuser;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylUsers extends AbstractPterodactylClientAdapter implements PterodactylUsersInterface
{
    public function getUsers(string $serverId): Collection
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/users");
        $data = $this->validateListResponse($response, 200);

        $items = array_map(
            fn(array $user) => new PterodactylSubuser($user['attributes']),
            $data['data'] ?? []
        );

        return new Collection($items, $this->getMetaFromResponse($data));
    }

    public function createUser(string $serverId, string $email, array $permissions): PterodactylSubuser
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/users", [
            'json' => [
                'email' => $email,
                'permissions' => $permissions
            ]
        ]);
        
        $data = $this->validateClientResponse($response, 201);
        return new PterodactylSubuser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function getUser(string $serverId, string $userId): PterodactylSubuser
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/users/{$userId}");
        $data = $this->validateClientResponse($response, 200);

        return new PterodactylSubuser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function updateUserPermissions(string $serverId, string $userId, array $permissions): PterodactylSubuser
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/users/{$userId}", [
            'json' => [
                'permissions' => $permissions
            ]
        ]);
        
        $data = $this->validateClientResponse($response, 200);
        return new PterodactylSubuser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function deleteUser(string $serverId, string $userId): void
    {
        $this->makeRequest('DELETE', "servers/{$serverId}/users/{$userId}");
    }
}
