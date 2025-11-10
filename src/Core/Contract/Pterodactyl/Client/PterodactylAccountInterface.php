<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylAccount;
use App\Core\DTO\Pterodactyl\Client\PterodactylApiKey;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylAccountInterface
{
    public function getAccount(): PterodactylAccount;

    public function updateAccount(array $details): PterodactylAccount;

    public function updateEmail(string $email, string $currentPassword): PterodactylAccount;

    public function updatePassword(string $currentPassword, string $newPassword, string $passwordConfirmation): PterodactylAccount;

    public function enableTwoFactor(string $code): bool;

    public function disableTwoFactor(string $password): bool;

    public function getTwoFactorQrCode(): string;

    public function getApiKeys(): Collection;

    public function createApiKey(string $description, array $allowedIps = []): PterodactylApiKey;

    public function deleteApiKey(string $identifier): bool;
}
