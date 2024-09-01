<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\NodeSelectionService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\SettingService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\Resources\Egg as PterodactylEgg;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class CreateServerService extends AbstractActionServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly NodeSelectionService $nodeSelectionService,
        private readonly SettingService $settingService,
        private readonly ServerService $serverService,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        UserRepository $userRepository,
    ) {
        parent::__construct($userRepository, $pterodactylService);
    }

    public function createServer(Product $product, int $eggId, User|UserInterface $user): Server
    {
        $createdPterodactylServer = $this->createPterodactylServer($product, $eggId, $user);
        $createdEntityServer = $this->createEntityServer($createdPterodactylServer, $product, $user);
        $this->updateUserBalance($user, $product->getPrice());
        $this->sendBoughtConfirmationEmail($user, $product, $createdEntityServer);
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

        $bestAllocationId = $this->nodeSelectionService->getBestAllocationId($product);
        $requestPayload = [
            'name' => sprintf('%s [%s]', $product->getName(), $user->getEmail()),
            'user' => $user->getPterodactylUserId(),
            'egg' => $selectedEgg->get('id'),
            'docker_image' => $selectedEgg->get('docker_image'),
            'startup' => $selectedEgg->get('startup'),
            'environment' => $this->prepareEnvironmentVariables($selectedEgg),
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
            ],
            'allocation' => [
                'default' => $bestAllocationId,
            ],
        ];

        return $this->pterodactylService->getApi()->servers->create($requestPayload);
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

    private function prepareEnvironmentVariables(PterodactylEgg $egg): array
    {
        $environmentVariables = [];
        if (!$egg->has('relationships')) {
            return $environmentVariables;
        }
        foreach ($egg->get('relationships')['variables']->data as $variable) {
            $environmentVariables[$variable->env_variable] = $variable->default_value;
        }
        return $environmentVariables;
    }

    private function sendBoughtConfirmationEmail(User $user, Product $product, Server $server): void
    {
        $serverDetails = $this->serverService->getServerDetails($server);
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.store.subject'),
            'email/purchased_product.html.twig',
            [
                'user' => $user,
                'product' => $product,
                'currency' => $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                'server' => [
                    'ip' => $serverDetails['ip'],
                    'expiresAt' => $server->getExpiresAt()->format('Y-m-d H:i'),
                ],
                'panel' => [
                    'url' => $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value),
                    'username' => $this->getPterodactylAccountLogin($user),
                ],
            ]
        );
        $this->messageBus->dispatch($emailMessage);
    }
}
