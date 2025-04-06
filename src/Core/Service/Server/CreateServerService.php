<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\ProductPrice;
use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\ServerProductPrice;
use App\Core\Entity\User;
use App\Core\Repository\ServerProductPriceRepository;
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
        private readonly ServerProductPriceRepository $serverProductPriceRepository,
        UserRepository $userRepository,
    ) {
        parent::__construct($userRepository, $pterodactylService);
    }

    public function createServer(Product $product, int $eggId, int $priceId, User|UserInterface $user): Server
    {
        $createdPterodactylServer = $this->createPterodactylServer($product, $eggId, $user);
        $createdEntityServer = $this->createEntityServer($createdPterodactylServer, $user, $product, $priceId);
        $createdEntityServerProduct = $this->createEntityServerProduct($createdEntityServer, $product);
        $this->createEntitiesServerProductPrice($createdEntityServerProduct, $priceId);
        $this->updateUserBalance($user, $product, $priceId);
        $this->boughtConfirmationEmailService->sendBoughtConfirmationEmail(
            $user,
            $createdEntityServer,
            $product,
            $priceId,
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

    private function createEntityServer(PterodactylServer $server, User $user, Product $product, int $priceId): Server
    {
        /** @var ProductPrice $selectedPrice */
        $selectedPrice = $product->getPrices()->filter(
            fn(ProductPrice $price) => $price->getId() === $priceId
        )->first();

        if (empty($selectedPrice)) {
            throw new \Exception('Price not found');
        }

        $datetimeModifier = sprintf(
            '+%d %s',
            $selectedPrice->getValue(),
            $selectedPrice->getUnit()->value
        );
        $entityServer = (new Server())
            ->setPterodactylServerId($server->get('id'))
            ->setPterodactylServerIdentifier($server->get('identifier'))
            ->setUser($user)
            ->setExpiresAt(new \DateTime($datetimeModifier));

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
            ->setMemory($product->getMemory())
            ->setIo($product->getIo())
            ->setCpu($product->getCpu())
            ->setDbCount($product->getDbCount())
            ->setSwap($product->getSwap())
            ->setBackups($product->getBackups())
            ->setPorts($product->getPorts())
            ->setNodes($product->getNodes())
            ->setNest($product->getNest())
            ->setEggs($product->getEggs())
            ->setEggsConfiguration($product->getEggsConfiguration())
            ->setAllowChangeEgg($product->getAllowChangeEgg());

        $this->serverProductRepository->save($entityServerProduct);

        return $entityServerProduct;
    }

    private function createEntitiesServerProductPrice(ServerProduct $serverProduct, int $selectedPriceId): void
    {
        foreach ($serverProduct->getOriginalProduct()->getPrices() as $price) {
            $serverProductPrice = (new ServerProductPrice())
                ->setServerProduct($serverProduct)
                ->setType($price->getType())
                ->setValue($price->getValue())
                ->setUnit($price->getUnit())
                ->setPrice($price->getPrice())
                ->setSelected($price->getId() === $selectedPriceId);

            $this->serverProductPriceRepository->save($serverProductPrice);
        }
    }
}
