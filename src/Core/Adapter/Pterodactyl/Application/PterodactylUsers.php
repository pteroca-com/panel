<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylUsersInterface;
use App\Core\DTO\Pterodactyl\Application\PterodactylUser;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylUsers extends AbstractPterodactylApplicationAdapter implements PterodactylUsersInterface
{
    public function getAllUsers(array $parameters = []): Collection
    {
        $response = $this->makeRequest('GET', 'users', ['query' => $parameters]);
        $data = $this->validateServerResponse($response, 200);

        $users = [];
        foreach ($data['data'] as $item) {
            $users[] = new PterodactylUser($item['attributes']);
        }

        return new Collection($users, $this->getMetaFromResponse($data));
    }

    public function getAllUsersPaginated(int $page = 1, array $parameters = []): Collection
    {
        $queryParams = array_merge(['page' => $page], $parameters);
        $response = $this->makeRequest('GET', 'users', ['query' => $queryParams]);
        $data = $this->validateServerResponse($response, 200);

        $users = [];
        foreach ($data['data'] as $item) {
            $users[] = new PterodactylUser($item['attributes']);
        }

        return new Collection($users, $this->getMetaFromResponse($data));
    }

    public function getUser(int|string $userId, array $parameters = []): PterodactylUser
    {
        $response = $this->makeRequest('GET', "users/{$userId}", ['query' => $parameters]);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylUser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function getUserByExternalId(string $externalId, array $parameters = []): PterodactylUser
    {
        $response = $this->makeRequest('GET', "users/external/{$externalId}", ['query' => $parameters]);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylUser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function updateUser(int|string $userId, array $details): PterodactylUser
    {
        $response = $this->makeRequest('PATCH', "users/{$userId}", ['json' => $details]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylUser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function createUser(array $details): PterodactylUser
    {
        $response = $this->makeRequest('POST', 'users', ['json' => $details]);
        $data = $this->validateServerResponse($response, 201);

        return new PterodactylUser($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function deleteUser(int|string $userId): bool
    {
        $response = $this->makeRequest('DELETE', "users/{$userId}");
        return in_array($response->getStatusCode(), [200, 204]);
    }
}
