<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylBackup;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylBackupsInterface
{
    public function getBackups(string $serverId): Collection;

    public function getBackup(string $serverId, string $backupId): PterodactylBackup;

    public function createBackup(string $serverId, ?string $name = null, ?array $ignoredFiles = null, bool $isLocked = false): PterodactylBackup;

    public function getBackupDownloadUrl(string $serverId, string $backupId): array;

    public function restoreBackup(string $serverId, string $backupId, bool $truncate = true): bool;

    public function toggleBackupLock(string $serverId, string $backupId): bool;

    public function deleteBackup(string $serverId, string $backupId): bool;
}
