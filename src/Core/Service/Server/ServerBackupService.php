<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class ServerBackupService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
    ) {}

    public function createBackup(
        Server $server,
        UserInterface $user,
        ?string $backupName,
        ?string $ignoredFiles,
        bool $isLocked = false,
    ): array
    {
        if (empty($backupName)) {
            throw new \InvalidArgumentException('Backup name is required');
        }

        $createdBackup = $this->getPterodactylClientApi($user)
            ->server_backups
            ->create($server->getPterodactylServerIdentifier(), [
                'name' => $backupName,
                'is_locked' => $isLocked,
                'ignored' => $ignoredFiles ?? '',
            ])->toArray();

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::CREATE_BACKUP,
            [
                'backup_id' => $createdBackup['uuid'],
                'backup_name' => $backupName,
            ]
        );

        return $createdBackup;
    }

    public function getBackupDownloadUrl(
        Server $server,
        UserInterface $user,
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

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DOWNLOAD_BACKUP,
            [
                'backup_id' => $backupId,
            ]
        );

        return $downloadUrl;
    }

    public function deleteBackup(
        Server $server,
        UserInterface $user,
        string $backupId,
    ): string
    {
        $deletedBackup = $this->getPterodactylClientApi($user)
            ->server_backups
            ->delete($server->getPterodactylServerIdentifier(), $backupId, []);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_BACKUP,
            [
                'backup_id' => $backupId,
            ]
        );

        return $deletedBackup;
    }

    public function restoreBackup(
        Server $server,
        UserInterface $user,
        string $backupId,
        bool $truncate = false,
    ): void
    {
        $endpointUrl = sprintf(
            'servers/%s/backups/%s/restore',
            $server->getPterodactylServerIdentifier(), $backupId
        );

        $requestData['truncate'] = $truncate;

        $this->getPterodactylClientApi($user)
            ->server_backups
            ->http
            ->post($endpointUrl, $requestData);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::RESTORE_BACKUP,
            [
                'backup_id' => $backupId,
                'truncate' => $truncate,
            ]
        );
    }

    private function getPterodactylClientApi(UserInterface $user): PterodactylApi
    {
        return $this->pterodactylClientService
            ->getApi($user);
    }
}
