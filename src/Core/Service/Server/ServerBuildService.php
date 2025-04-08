<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\User;
use App\Core\Service\Pterodactyl\NodeSelectionService;
use App\Core\Service\Pterodactyl\PterodactylService;
use JsonException;
use Timdesm\PterodactylPhpApi\Resources\Egg as PterodactylEgg;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class ServerBuildService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly NodeSelectionService $nodeSelectionService,
    ) {}

    public function prepareServerBuild(Product|ServerProduct $product, User $user, int $eggId): array
    {
        $selectedEgg = $this->pterodactylService->getApi()->nest_eggs->get(
            $product->getNest(),
            $eggId,
            ['include' => 'variables']
        );
        if (!$selectedEgg->has('id')) {
            throw new \Exception('Egg not found');
        }

        try {
            $productEggConfiguration = json_decode(
                $product->getEggsConfiguration(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $productEggConfiguration = [];
        }

        $bestAllocationId = $this->nodeSelectionService->getBestAllocationId($product);
        $dockerImage = $productEggConfiguration[$eggId]['options']['docker_image']['value']
            ?? $selectedEgg->get('docker_image');
        $startup = $productEggConfiguration[$eggId]['options']['startup']['value']
            ?? $selectedEgg->get('startup');

        return [
            'name' => $product->getName(),
            'user' => $user->getPterodactylUserId(),
            'egg' => $selectedEgg->get('id'),
            'docker_image' => $dockerImage,
            'startup' => $startup,
            'environment' => $this->prepareEnvironmentVariables($selectedEgg, $productEggConfiguration),
            'limits' => [
                'memory' => $product->getMemory(),
                'swap' => $product->getSwap(),
                'disk' => $product->getDiskSpace(),
                'io' => $product->getIo(),
                'cpu' => $product->getCpu(),
                'threads' => null, // TODO implement
            ],
            'feature_limits' => [
                'databases' => $product->getDbCount(),
                'backups' => $product->getBackups(),
                'allocations' => $product->getPorts(),
            ],
            'allocation' => [
                'default' => $bestAllocationId,
            ],
        ];
    }

    public function prepareUpdateServerBuild(ServerProduct $product, PterodactylServer $pterodactylServer): array
    {
        return [
            'allocation' => $pterodactylServer->get('allocation'),
            'memory' => $product->getMemory(),
            'swap' => $product->getSwap(),
            'io' => $product->getIo(),
            'cpu' => $product->getCpu(),
            'disk' => $product->getDiskSpace(),
            'feature_limits' => [
                'databases' => $product->getDbCount(),
                'backups' => $product->getBackups(),
                'allocations' => $product->getPorts(),
            ]
        ];
    }

    public function prepareUpdateServerStartup(ServerProduct $product, PterodactylServer $pterodactylServer): array
    {
        $eggId = $pterodactylServer->get('egg');
        $selectedEgg = $this->pterodactylService->getApi()->nest_eggs->get(
            $product->getNest(),
            $eggId,
            ['include' => 'variables']
        );
        if (!$selectedEgg->has('id')) {
            throw new \Exception('Egg not found in nest');
        }

        try {
            $productEggConfiguration = json_decode(
                $product->getEggsConfiguration(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $productEggConfiguration = [];
        }

        $dockerImage = $productEggConfiguration[$eggId]['options']['docker_image']['value']
            ?? $selectedEgg->get('docker_image');
        $startup = $productEggConfiguration[$eggId]['options']['startup']['value']
            ?? $selectedEgg->get('startup');

        return [
            'startup' => $startup,
            'environment' => $this->prepareEnvironmentVariables($selectedEgg, $productEggConfiguration),
            'egg' => $eggId,
            'image' => $dockerImage,
            'skip_scripts' => false,
        ];
    }

    private function prepareEnvironmentVariables(PterodactylEgg $egg, array $productEggConfiguration): array
    {
        $environmentVariables = [];

        if (!$egg->has('relationships')) {
            return $environmentVariables;
        }

        foreach ($egg->get('relationships')['variables']->data as $variable) {
            $variableToSet = $productEggConfiguration[$egg->get('id')]['variables'][$variable->get('id')]['value']
                ?? $variable->default_value;
            $environmentVariables[$variable->env_variable] = $variableToSet;
        }

        return $environmentVariables;
    }
}
