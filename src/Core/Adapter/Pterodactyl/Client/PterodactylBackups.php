<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylBackupsInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylBackup;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylBackups extends AbstractPterodactylClientAdapter implements PterodactylBackupsInterface
{
    public function getBackups(string $serverId): Collection
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/backups");
        $data = $this->validateListResponse($response, 200);

        $backups = [];
        foreach ($data['data'] as $backupData) {
            $backups[] = new PterodactylBackup($backupData['attributes']);
        }

        return new Collection($backups, $this->getMetaFromResponse($data));
    }

    public function getBackup(string $serverId, string $backupId): PterodactylBackup
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/backups/{$backupId}");
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylBackup($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function createBackup(string $serverId, ?string $name = null, ?array $ignoredFiles = null, bool $isLocked = false): PterodactylBackup
    {
        $payload = [];
        if ($name !== null) {
            $payload['name'] = $name;
        }
        if ($ignoredFiles !== null) {
            $payload['ignored'] = implode("\n", $ignoredFiles);
        }
        if ($isLocked) {
            $payload['is_locked'] = $isLocked;
        }

        $response = $this->makeRequest('POST', "servers/{$serverId}/backups", ['json' => $payload]);
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylBackup($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function getBackupDownloadUrl(string $serverId, string $backupId): array
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/backups/{$backupId}/download");
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve backup download URL');
        }

        $data = $response->toArray();
        return $this->getDataFromResponse($data) ?: $data;
    }

    public function restoreBackup(string $serverId, string $backupId, bool $truncate = true): bool
    {
        $payload = ['truncate' => $truncate];
        $response = $this->makeRequest('POST', "servers/{$serverId}/backups/{$backupId}/restore", ['json' => $payload]);
        return in_array($response->getStatusCode(), [200, 204]);
    }

    public function toggleBackupLock(string $serverId, string $backupId): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/backups/{$backupId}/lock");
        return in_array($response->getStatusCode(), [200, 204]);
    }

    public function deleteBackup(string $serverId, string $backupId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/{$serverId}/backups/{$backupId}");
        return $response->getStatusCode() === 204;
    }
}
