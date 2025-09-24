<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Product;
use App\Core\Entity\ProductPrice;
use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\ServerProductPrice;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\VoucherTypeEnum;
use App\Core\Repository\ServerProductPriceRepository;
use App\Core\Repository\ServerProductRepository;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Mailer\BoughtConfirmationEmailService;
use App\Core\Service\Product\ProductPriceCalculatorService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Voucher\VoucherPaymentService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        private readonly LogService $logService,
        private readonly VoucherPaymentService $voucherPaymentService,
        private readonly TranslatorInterface $translator,
        UserRepository $userRepository,
        ProductPriceCalculatorService $productPriceCalculatorService,
        LoggerInterface $logger,
    ) {
        parent::__construct($userRepository, $pterodactylService, $voucherPaymentService, $productPriceCalculatorService, $translator, $logger);
    }

    public function createServer(
        Product $product,
        int $eggId,
        int $priceId,
        string $serverName,
        bool $autoRenewal,
        UserInterface $user,
        ?string $voucherCode = null,
        ?int $slots = null,
    ): Server
    {
        if (!empty($voucherCode)) {
            $this->voucherPaymentService->validateVoucherCode(
                $voucherCode,
                $user,
                VoucherTypeEnum::SERVER_DISCOUNT,
            );
        }

        $createdPterodactylServer = $this->createPterodactylServer($product, $eggId, $serverName, $user, $slots);

        $createdEntityServer = $this->createEntityServer(
            $createdPterodactylServer,
            $user,
            $product,
            $priceId,
            $autoRenewal
        );
        $createdEntityServerProduct = $this->createEntityServerProduct($createdEntityServer, $product);
        $this->createEntitiesServerProductPrice($createdEntityServerProduct, $priceId);

        $this->updateUserBalance($user, $product, $priceId, $voucherCode, $slots);
        $this->boughtConfirmationEmailService->sendBoughtConfirmationEmail(
            $user,
            $createdEntityServer,
            $product,
            $priceId,
            $this->getPterodactylAccountLogin($user),
        );

        $this->logService->logAction(
            $user,
            LogActionEnum::BOUGHT_SERVER,
            [
                'product' => $product,
                'egg' => $eggId,
                'price' => $priceId,
                'voucher' => $voucherCode,
                'server' => $createdEntityServer,
            ],
        );

        return $createdEntityServer;
    }

    private function createPterodactylServer(
        Product $product,
        int $eggId,
        string $serverName,
        UserInterface $user,
        ?int $slots = null
    ): PterodactylServer
    {
        try {
            $preparedServerBuild = $this->serverBuildService
                ->prepareServerBuild($product, $user, $eggId, $serverName, $slots);

            return $this->pterodactylService
                ->getApi()
                ->servers
                ->create($preparedServerBuild);
        } catch (ValidationException $exception) {
            $errors = array_map(
                fn($error) => $error['detail'],
                $exception->errors()['errors']
            );
            $errors = implode(', ', $errors);
            throw new \Exception($errors);
        }
    }

    private function createEntityServer(
        PterodactylServer $server,
        UserInterface $user,
        Product $product,
        int $priceId,
        bool $autoRenewal
    ): Server
    {
        /** @var ?ProductPrice $selectedPrice */
        $selectedPrice = $product->getPrices()->filter(
            fn(ProductPrice $price) => $price->getId() === $priceId
        )->first() ?: null;

        if (empty($selectedPrice)) {
            throw new \Exception($this->translator->trans('pteroca.store.price_not_found'));
        }

        $datetimeModifier = sprintf(
            '+%d %s',
            $selectedPrice->getValue(),
            $selectedPrice->getUnit()->value
        );
        $autoRenewalStatus = $autoRenewal || $selectedPrice->getType() === ProductPriceTypeEnum::ON_DEMAND;
        $entityServer = (new Server())
            ->setPterodactylServerId($server->get('id'))
            ->setPterodactylServerIdentifier($server->get('identifier'))
            ->setUser($user)
            ->setExpiresAt(new \DateTime($datetimeModifier))
            ->setAutoRenewal($autoRenewalStatus);

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
        
        $server->setServerProduct($entityServerProduct);
        $this->serverRepository->save($server);

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
                ->setIsSelected($price->getId() === $selectedPriceId);

            $this->serverProductPriceRepository->save($serverProductPrice);
        }
    }
}
