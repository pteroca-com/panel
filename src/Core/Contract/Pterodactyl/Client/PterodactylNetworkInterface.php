<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylAllocation;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylNetworkInterface
{
    public function getAllocations(string $serverId): Collection;

    public function assignAllocation(string $serverId, ?string $ip = null, ?int $port = null): PterodactylAllocation;

    public function setPrimaryAllocation(string $serverId, int $allocationId): bool;

    public function updateAllocationNotes(string $serverId, int $allocationId, string $notes): bool;

    public function removeAllocation(string $serverId, int $allocationId): bool;
}
