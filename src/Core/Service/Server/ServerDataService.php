<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Collection\ServerPermissionCollection;
use App\Core\DTO\Collection\ServerVariableCollection;
use App\Core\DTO\ServerDataDTO;
use App\Core\Entity\Server;
use App\Core\Enum\ServerPermissionEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Exception\UserDoesNotHaveClientApiKeyException;
use App\Core\Factory\ServerVariableFactory;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Exception;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class ServerDataService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerNestService $serverNestService,
        private readonly ServerService $serverService,
        private readonly ServerVariableFactory $serverVariableFactory,
    )
    {
    }

    public function getServerData(Server $server, UserInterface $user): ServerDataDTO
    {
        /** @var PterodactylServer $pterodactylServer */
        $pterodactylServer = $this->pterodactylService
            ->getApi()
            ->servers
            ->get($server->getPterodactylServerId(), [
                'include' => ['variables', 'egg', 'databases', 'subusers'],
            ]);
        
        $permissions = $this->getPermissions($pterodactylServer, $server, $user);
        $dockerImages = $pterodactylServer->get('relationships')['egg']->get('docker_images');

        try {
            $pterodactylClientApi = $this->pterodactylClientService
                ->getApi($server->getUser());
        } catch (UserDoesNotHaveClientApiKeyException) {
            $pterodactylClientApi = null;
        }

        try {
            $allocatedPorts = $pterodactylClientApi->servers
                ->http
                ->get(sprintf('servers/%s/network/allocations', $server->getPterodactylServerIdentifier()))
                ->toArray();
            $allocatedPorts = array_map(function ($allocation) {
                return $allocation->toArray();
            }, $allocatedPorts);
        } catch (Exception $exception) {
            $allocatedPorts = [];
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
            $productEggConfiguration = [];
        }

        if ($server->getServerProduct()->getAllowChangeEgg()) {
            $availableNestEggs = $this->serverNestService->getServerAvailableEggs($server);
        }

        [$hasConfigurableOptions, $hasConfigurableVariables] = $this->getServerConfigurableOptionsAndVariables(
            $server,
            $pterodactylServer->get('egg')
        );

        $serverVariables = $this->serverVariableFactory
            ->createFromCollection($pterodactylServer->get('relationships')['variables']->all());

        if ($server->getServerProduct()->getBackups()) {
            try {
                $serverBackups = $pterodactylClientApi
                    ->server_backups
                    ->http
                    ->get(sprintf('servers/%s/backups', $server->getPterodactylServerIdentifier()))
                    ->toArray();
            } catch (Exception) {
                $serverBackups = [];
            }
        }

        $subusers = $pterodactylClientApi->servers
            ->http
            ->get(sprintf(
                'servers/%s/users',
                $server->getPterodactylServerIdentifier(),
            ))
            ->toArray();

        return new ServerDataDTO(
            $permissions,
            $this->serverService->getServerDetails($server),
            $pterodactylServer->toArray(),
            $dockerImages,
            $pterodactylClientServer?->toArray(),
            $pterodactylClientAccount?->toArray(),
            $productEggConfiguration,
            $availableNestEggs ?? null,
            $hasConfigurableOptions,
            $hasConfigurableVariables,
            new ServerVariableCollection($serverVariables),
            $serverBackups ?? [],
            $allocatedPorts,
            $subusers,
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

    private function getPermissions(PterodactylServer $pterodactylServer, Server $server, UserInterface $user): ServerPermissionCollection
    {
        $isAdmin = !empty(array_filter(
            $user->getRoles(),
            fn($role) => $role === UserRoleEnum::ROLE_ADMIN->name,
        ));
        $isServerOwner = $server->getUser()->getId() === $user->getId();

        if (!$isAdmin && !$isServerOwner) {
            $subUser = current(array_filter(
                $pterodactylServer->get('relationships')['subusers']->toArray(),
                fn($subuser) => $subuser['attributes']['user_id'] === $user->getPterodactylUserId(),
            ));

            return ServerPermissionEnum::fromArray($subUser['attributes']['permissions'] ?? []);
        }

        $allPermissions = [];
        foreach (ServerPermissionEnum::cases() as $permission) {
            $allPermissions[] = $permission->value;
        }
        
        return ServerPermissionEnum::fromArray($allPermissions);
    }
}
