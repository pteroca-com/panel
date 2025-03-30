<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Mailer\BoughtConfirmationEmailService;
use App\Core\Service\Pterodactyl\NodeSelectionService;
use App\Core\Service\Pterodactyl\PterodactylService;
use JsonException;
use Symfony\Component\Security\Core\User\UserInterface;
use Timdesm\PterodactylPhpApi\Exceptions\ValidationException;
use Timdesm\PterodactylPhpApi\Resources\Egg as PterodactylEgg;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class CreateServerService extends AbstractActionServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly NodeSelectionService $nodeSelectionService,
        private readonly BoughtConfirmationEmailService $boughtConfirmationEmailService,
        UserRepository $userRepository,
    ) {
        parent::__construct($userRepository, $pterodactylService);
    }

    public function createServer(Product $product, int $eggId, User|UserInterface $user): Server
    {
        $createdPterodactylServer = $this->createPterodactylServer($product, $eggId, $user);
        $createdEntityServer = $this->createEntityServer($createdPterodactylServer, $product, $user);
        $this->updateUserBalance($user, $product->getPrice());
        $this->boughtConfirmationEmailService->sendBoughtConfirmationEmail(
            $user,
            $product,
            $createdEntityServer,
            $this->getPterodactylAccountLogin($user),
        );

        return $createdEntityServer;
    }

    private function createPterodactylServer(Product $product, int $eggId, User $user): PterodactylServer
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

        $requestPayload = [
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

        try {
            return $this->pterodactylService->getApi()->servers->create($requestPayload);
        } catch (ValidationException $exception) {
            $errors = array_map(
                fn($error) => $error['detail'],
                $exception->errors()['errors']
            );
            $errors = implode(', ', $errors);
            throw new \Exception($errors);
        }
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

    private function createEntityServer(PterodactylServer $server, Product $product, User $user): Server
    {
        $entityServer = (new Server())
            ->setPterodactylServerId($server->get('id'))
            ->setPterodactylServerIdentifier($server->get('identifier'))
            ->setProduct($product)
            ->setUser($user)
            ->setExpiresAt(new \DateTime('+1 month'));

        $this->serverRepository->save($entityServer);
        return $entityServer;
    }
}
