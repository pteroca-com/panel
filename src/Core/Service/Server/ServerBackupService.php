<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class ServerBackupService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
    ) {}

    public function createBackup(
        Server $server,
        User $user,
        ?string $backupName,
        ?string $ignoredFiles,
        bool $isLocked = false,
    ): array
    {
        if (empty($backupName)) {
            throw new \InvalidArgumentException('Backup name is required');
        }

        return $this->getPterodactylClientApi($user)
            ->server_backups
            ->create($server->getPterodactylServerIdentifier(), [
                'name' => $backupName,
                'is_locked' => $isLocked,
                'ignored' => $ignoredFiles ?? '',
            ])->toArray();
    }

    public function getBackupDownloadUrl(
        Server $server,
        User $user,
        string $backupId,
    ): string
    {
        $endpointUrl = sprintf(
            'servers/%s/backups/%s/download',
            $server->getPterodactylServerIdentifier(), $backupId
        );

        $downloadUrl = $this->getPterodactylClientApi($user)
            ->server_backups
            ->http
            ->get($endpointUrl)
            ->toArray()['url'] ?? null;

        if (empty($downloadUrl)) {
            throw new \RuntimeException('Failed to get backup download URL');
        }

        return $downloadUrl;
    }

    public function deleteBackup(
        Server $server,
        User $user,
        string $backupId,
    ): string
    {
        return $this->getPterodactylClientApi($user)
            ->server_backups
            ->delete($server->getPterodactylServerIdentifier(), $backupId, []);
    }

    private function getPterodactylClientApi(User $user): PterodactylApi
    {
        return $this->pterodactylClientService
            ->getApi($user);
    }
}