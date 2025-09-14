<?php

namespace App\Core\Adapter\Pterodactyl;

use App\Core\Contract\Pterodactyl\PterodactylUsersInterface;
use App\Core\DTO\Pterodactyl\PterodactylUser;

class PterodactylUsers extends AbstractPterodactylAdapter implements PterodactylUsersInterface
{
    public function getAllUsers(array $parameters = []): array
    {
        $response = $this->makeRequest('GET', 'users', ['query' => $parameters]);
        $data = $this->validateServerResponse($response, 200);

        $users = [];
        foreach ($data as $item) {
            $users[] = new PterodactylUser($item['attributes']);
        }

        return $users;
    }

    public function getAllUsersPaginated(int $page = 1, array $parameters = []): array
    {
        $queryParams = array_merge(['page' => $page], $parameters);
        $response = $this->makeRequest('GET', 'users', ['query' => $queryParams]);
        $data = $this->validateServerResponse($response, 200);

        $users = [];
        foreach ($data as $item) {
            $users[] = new PterodactylUser($item['attributes']);
        }

        return $users;
    }

    public function getUser(int|string $userId, array $parameters = []): PterodactylUser
    {
        $response = $this->makeRequest('GET', "users/{$userId}", ['query' => $parameters]);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylUser($data['attributes']);
    }

    public function getUserByExternalId(string $externalId, array $parameters = []): PterodactylUser
    {
        $response = $this->makeRequest('GET', "users/external/{$externalId}", ['query' => $parameters]);
        $data = $this->validateServerResponse($response, 200);
        
        return new PterodactylUser($data['attributes']);
    }

    public function updateUser(int|string $userId, array $details): PterodactylUser
    {
        $response = $this->makeRequest('PATCH', "users/{$userId}", ['json' => $details]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylUser($data['attributes']);
    }

    public function createUser(array $details): PterodactylUser
    {
        $response = $this->makeRequest('POST', 'users', ['json' => $details]);
        $data = $this->validateServerResponse($response, 201);

        return new PterodactylUser($data['attributes']);
    }

    public function deleteUser(int|string $userId): bool
    {
        $response = $this->makeRequest('DELETE', "users/{$userId}");
        return in_array($response->getStatusCode(), [200, 204]);
    }
}
