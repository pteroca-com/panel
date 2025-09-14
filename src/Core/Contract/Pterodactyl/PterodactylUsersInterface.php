<?php

namespace App\Core\Contract\Pterodactyl;

use App\Core\DTO\Pterodactyl\PterodactylUser;

interface PterodactylUsersInterface
{
    public function getAllUsers(array $parameters = []): array;

    public function getUser(string $userId): PterodactylUser;

    public function updateUser(string $userId, array $details): PterodactylUser;

    public function createUser(array $details): PterodactylUser;

    public function deleteUser(string $userId): bool;
}