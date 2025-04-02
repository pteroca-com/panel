<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\User;
use App\Core\Repository\ServerProductRepository;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Mailer\BoughtConfirmationEmailService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Component\Security\Core\User\UserInterface;
use Timdesm\PterodactylPhpApi\Exceptions\ValidationException;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class CreateServerService extends AbstractActionServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly ServerProductRepository $serverProductRepository,
        private readonly BoughtConfirmationEmailService $boughtConfirmationEmailService,
        private readonly ServerBuildService $serverBuildService,
        UserRepository $userRepository,
    ) {
        parent::__construct($userRepository, $pterodactylService);
    }

    public function createServer(Product $product, int $eggId, User|UserInterface $user): Server
    {
        $createdPterodactylServer = $this->createPterodactylServer($product, $eggId, $user);
        $createdEntityServer = $this->createEntityServer($createdPterodactylServer, $user);
        $createdEntityServerProduct = $this->createEntityServerProduct($createdEntityServer, $product);
        $this->updateUserBalance($user, $product->getPrice()); // TODO set price
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
        try {
            $preparedServerBuild = $this->serverBuildService->prepareServerBuild($product, $user, $eggId);
            return $this->pterodactylService->getApi()->servers->create($preparedServerBuild);
        } catch (ValidationException $exception) {
            $errors = array_map(
                fn($error) => $error['detail'],
                $exception->errors()['errors']
            );
            $errors = implode(', ', $errors);
            throw new \Exception($errors);
        }
    }

    private function createEntityServer(PterodactylServer $server, User $user): Server
    {
        $entityServer = (new Server())
            ->setPterodactylServerId($server->get('id'))
            ->setPterodactylServerIdentifier($server->get('identifier'))
            ->setUser($user)
            ->setExpiresAt(new \DateTime('+1 month'));

        $this->serverRepository->save($entityServer);

        return $entityServer;
    }

    private function createEntityServerProduct(Server $server, Product $product): ServerProduct
    {
        $entityServerProduct = (new ServerProduct())
            ->setServer($server)
            ->setOriginalProduct($product)
            ->setName($product->getName())
            ->setDiskSpace($product->getDiskSpace())
            ->setEggs($product->getEggs())
            ->setEggsConfiguration($product->getEggsConfiguration())
            ->setAllowChangeEgg($product->getAllowChangeEgg());

        $this->serverProductRepository->save($entityServerProduct);

        return $entityServerProduct;
    }
}
