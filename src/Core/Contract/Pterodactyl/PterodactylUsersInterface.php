<?php

namespace App\Core\Contract\Pterodactyl;

use App\Core\DTO\Pterodactyl\PterodactylUser;

interface PterodactylUsersInterface
{
    public function getAllUsers(array $parameters = []): array;

    public function getAllUsersPaginated(int $page = 1, array $parameters = []): array;

    public function getUser(int|string $userId, array $parameters = []): PterodactylUser;

    public function getUserByExternalId(string $externalId, array $parameters = []): PterodactylUser;

    public function updateUser(int|string $userId, array $details): PterodactylUser;

    public function createUser(array $details): PterodactylUser;

    public function deleteUser(int|string $userId): bool;
}
