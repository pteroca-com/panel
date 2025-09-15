<?php

namespace App\Core\Service\Server;

use App\Core\Contract\Pterodactyl\Client\PterodactylClientAdapterInterface;
use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

class ServerBackupService
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
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
            ->backups()
            ->createBackup($server, $backupName, $ignoredFiles, $isLocked)
            ->toArray();

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
        $downloadUrl = $this->getPterodactylClientApi($user)
            ->backups()
            ->getBackupDownloadUrl($server, $backupId)['url'] ?? null;

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
            ->backups()
            ->deleteBackup($server, $backupId);

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
        $this->getPterodactylClientApi($user)
            ->backups()
            ->restoreBackup($server, $backupId, $truncate);

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

    private function getPterodactylClientApi(UserInterface $user): PterodactylClientAdapterInterface
    {
        return $this->pterodactylApplicationService
            ->getClientApi($user);
    }
}
