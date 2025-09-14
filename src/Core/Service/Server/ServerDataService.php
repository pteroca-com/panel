<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Collection\ServerVariableCollection;
use App\Core\DTO\ServerDataDTO;
use App\Core\Entity\Server;
use App\Core\Enum\ServerPermissionEnum;
use App\Core\Enum\ServerStatusEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Exception\UserDoesNotHaveClientApiKeyException;
use App\Core\Factory\ServerVariableFactory;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\SettingService;
use App\Core\Trait\ServerPermissionsTrait;
use Exception;
use Psr\Log\LoggerInterface;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class ServerDataService
{
    use ServerPermissionsTrait;

    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerNestService $serverNestService,
        private readonly ServerService $serverService,
        private readonly ServerVariableFactory $serverVariableFactory,
        private readonly ServerLogService $serverLogService,
        private readonly SettingService $settingService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function getServerData(Server $server, UserInterface $user, int $currentPage): ServerDataDTO
    {
        /** @var PterodactylServer $pterodactylServer */
        $pterodactylServer = $this->pterodactylService
            ->getApi()
            ->servers
            ->get($server->getPterodactylServerId(), [
                'include' => ['variables', 'egg', 'databases', 'subusers'],
            ]);
        
        $isInstalling = $this->isServerInstalling($pterodactylServer);
        if ($isInstalling) {
            return new ServerDataDTO(
                pterodactylServer: $pterodactylServer->toArray(),
                isInstalling: true,
            );
        }
        
        $isSuspended = $server->getIsSuspended();
        if ($isSuspended) {
            return new ServerDataDTO(
                pterodactylServer: $pterodactylServer->toArray(),
                isInstalling: false,
                isSuspended: true,
            );
        }
        
        $permissions = $this->getServerPermissions($pterodactylServer, $server, $user);

        try {
            $pterodactylClientApi = $this->pterodactylClientService
                ->getApi($user);
        } catch (UserDoesNotHaveClientApiKeyException $e) {
            $pterodactylClientApi = null;
        }

        if ($permissions->hasPermission(ServerPermissionEnum::ALLOCATION_READ)) {
            try {
                $allocatedPorts = $pterodactylClientApi->servers
                    ->http
                    ->get(sprintf('servers/%s/network/allocations', $server->getPterodactylServerIdentifier()))
                    ->toArray();
                $allocatedPorts = array_map(function ($allocation) {
                    return $allocation->toArray();
                }, $allocatedPorts);
            } catch (Exception $exception) {
                $this->logger->error('Failed to get allocated ports for server', [
                    'server_id' => $server->getId(),
                    'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ]);
            }
        }

        $pterodactylClientServer = $pterodactylClientApi
            ?->servers
            ->get($server->getPterodactylServerIdentifier());
        $pterodactylClientAccount = $pterodactylClientApi
            ?->account
            ->details();
        $productEggsConfiguration = $server->getServerProduct()->getEggsConfiguration();

        try {
            $productEggsConfiguration = json_decode(
                $productEggsConfiguration,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $productEggConfiguration = $productEggsConfiguration[$pterodactylServer->get('egg')] ?? [];
        } catch (Exception $e) {
            $this->logger->error('Failed to decode product eggs configuration', [
                'server_id' => $server->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $productEggConfiguration = [];
        }

        if ($server->getServerProduct()->getAllowChangeEgg() && $permissions->hasPermission(ServerPermissionEnum::SETTINGS_REINSTALL)) {
            $availableNestEggs = $this->serverNestService->getServerAvailableEggs($server);
        }

        if ($permissions->hasPermission(ServerPermissionEnum::STARTUP_READ)) {
            $dockerImages = $pterodactylServer->get('relationships')['egg']->get('docker_images');
            [$hasConfigurableOptions, $hasConfigurableVariables] = $this->getServerConfigurableOptionsAndVariables(
                $server,
                $pterodactylServer->get('egg')
            );
            $serverVariables = $this->serverVariableFactory
                ->createFromCollection($pterodactylServer->get('relationships')['variables']->all());
        }

        if ($server->getServerProduct()->getBackups() && $permissions->hasPermission(ServerPermissionEnum::BACKUP_READ)) {
            try {
                $serverBackups = $pterodactylClientApi
                    ->server_backups
                    ->http
                    ->get(sprintf('servers/%s/backups', $server->getPterodactylServerIdentifier()))
                    ->toArray();
            } catch (Exception $exception) {
                $this->logger->error('Failed to get server backups', [
                    'server_id' => $server->getId(),
                    'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ]);
            }
        }

        if ($permissions->hasPermission(ServerPermissionEnum::USER_READ)) {
            $subusers = $pterodactylClientApi->servers
                ->http
                ->get(sprintf(
                    'servers/%s/users',
                    $server->getPterodactylServerIdentifier(),
                ))
                ->toArray();
        }

        if ($permissions->hasPermission(ServerPermissionEnum::ACTIVITY_READ)) {
            $pterodactylActivityLogs = [];

            $showPterodactylLogs = (bool)$this->settingService->getSetting(SettingEnum::SHOW_PTERODACTYL_LOGS_IN_SERVER_ACTIVITY->value);

            if ($showPterodactylLogs) {
                $pterodactylActivityLogs = $pterodactylClientApi->servers
                    ->http
                    ->get(sprintf(
                        'servers/%s/activity',
                        $server->getPterodactylServerIdentifier(),
                    ))->toArray();
            }

            $activityLogs = $this->serverLogService->getServerLogsWithPagination(
                $server,
                $pterodactylActivityLogs,
                $currentPage,
            )->toArray();
        }

        if ($permissions->hasPermission(ServerPermissionEnum::SCHEDULE_READ)) {
            try {
                $serverSchedules = $pterodactylClientApi
                    ->servers
                    ->http
                    ->get(sprintf('servers/%s/schedules', $server->getPterodactylServerIdentifier()))
                    ->toArray();
                $serverSchedules = array_map(function ($schedule) {
                    return $schedule->toArray();
                }, $serverSchedules);
            } catch (Exception $exception) {
                $this->logger->error('Failed to get server schedules', [
                    'server_id' => $server->getId(),
                    'pterodactyl_server_identifier' => $server->getPterodactylServerIdentifier(),
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ]);
            }
        }

        return new ServerDataDTO(
            pterodactylServer: $pterodactylServer->toArray(),
            isInstalling: $isInstalling,
            server: $server,
            serverPermissions: $permissions,
            serverDetails: $this->serverService->getServerDetails($server),
            dockerImages: $dockerImages ?? [],
            pterodactylClientServer: $pterodactylClientServer?->toArray(),
            pterodactylClientAccount: $pterodactylClientAccount?->toArray(),
            productEggConfiguration: $productEggConfiguration,
            availableNestEggs: $availableNestEggs ?? null,
            hasConfigurableOptions: $hasConfigurableOptions ?? false,
            hasConfigurableVariables: $hasConfigurableVariables ?? false,
            serverVariables: new ServerVariableCollection($serverVariables ?? []),
            serverBackups: $serverBackups ?? [],
            allocatedPorts: $allocatedPorts ?? [],
            subusers: $subusers ?? [],
            activityLogs: $activityLogs ?? [],
            serverSchedules: $serverSchedules ?? [],
        );
    }

    private function getServerConfigurableOptionsAndVariables(Server $server, int $currentEgg): array
    {
        $productEggConfiguration = $server->getServerProduct()->getEggsConfiguration();
        if (empty($productEggConfiguration)) {
            return [false, false];
        }

        try {
            $productEggConfiguration = json_decode($productEggConfiguration, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            return [false, false];
        }

        $currentEggConfiguration = $productEggConfiguration[$currentEgg] ?? [];
        if (empty($currentEggConfiguration)) {
            return [false, false];
        }

        $hasConfigurableOptions = !empty(array_filter(array_values($currentEggConfiguration['options']), function ($configuration) {
            return !empty($configuration['user_viewable']) && $configuration['user_viewable'] === 'on';
        }));

        $hasConfigurableVariables = !empty(array_filter(array_values($currentEggConfiguration['variables']), function ($configuration) {
            return !empty($configuration['user_viewable']) && $configuration['user_viewable'] === 'on';
        }));

        return [$hasConfigurableOptions, $hasConfigurableVariables];
    }

    private function isServerInstalling(PterodactylServer $pterodactylServer): bool
    {
        $serverStatus = $pterodactylServer->get('status');
        
        return $serverStatus === ServerStatusEnum::INSTALLING->value;
    }
}
