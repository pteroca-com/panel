<?php

namespace App\Core\Service\Server;

use App\Core\DTO\Collection\ServerVariableCollection;
use App\Core\DTO\ServerDataDTO;
use App\Core\Entity\Server;
use App\Core\Exception\UserDoesNotHaveClientApiKeyException;
use App\Core\Factory\ServerVariableFactory;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;

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

    public function getServerData(Server $server): ServerDataDTO
    {
        $pterodactylServer = $this->pterodactylService
            ->getApi()
            ->servers
            ->get($server->getPterodactylServerId(), [
                'include' => ['variables', 'egg'],
            ]);
        $dockerImages = $pterodactylServer->get('relationships')['egg']->get('docker_images');

        try {
            $pterodactylClientApi = $this->pterodactylClientService
                ->getApi($server->getUser());
        } catch (UserDoesNotHaveClientApiKeyException) {
            $pterodactylClientApi = null;
        }

        $pterodactylClientServer = $pterodactylClientApi
            ?->servers
            ->get($server->getPterodactylServerIdentifier());
        $pterodactylClientAccount = $pterodactylClientApi
            ?->account
            ->details();
        $productEggsConfiguration = $server->getProduct()->getEggsConfiguration();

        try {
            $productEggsConfiguration = json_decode(
                $productEggsConfiguration,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $productEggConfiguration = $productEggsConfiguration[$pterodactylServer->get('egg')] ?? [];
        } catch (\Exception $e) {
            $productEggConfiguration = [];
        }

        if ($server->getProduct()->getAllowChangeEgg()) {
            $availableNestEggs = $this->serverNestService->getServerAvailableEggs($server);
        }

        [$hasConfigurableOptions, $hasConfigurableVariables] = $this->getServerConfigurableOptionsAndVariables(
            $server,
            $pterodactylServer->get('egg')
        );

        $serverVariables = $this->serverVariableFactory
            ->createFromCollection($pterodactylServer->get('relationships')['variables']->all());

        return new ServerDataDTO(
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
        );
    }

    private function getServerConfigurableOptionsAndVariables(Server $server, int $currentEgg): array
    {
        $productEggConfiguration = $server->getProduct()->getEggsConfiguration();
        if (empty($productEggConfiguration)) {
            return [false, false];
        }

        try {
            $productEggConfiguration = json_decode($productEggConfiguration, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
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
}
