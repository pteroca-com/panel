<?php

namespace App\Core\Handler;

use App\Core\Entity\Server;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\Mailer\ServerSuspensionEmailService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\Server\ServerSlotPricingService;
use App\Core\Service\StoreService;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class SuspendUnpaidServersHandler implements HandlerInterface
{

    public function __construct(
        private ServerRepository $serverRepository,
        private PterodactylService $pterodactylService,
        private StoreService $storeService,
        private RenewServerService $renewServerService,
        private ServerSlotPricingService $serverSlotPricingService,
        private TranslatorInterface $translator,
        private MessageBusInterface $messageBus,
        private EmailNotificationService $emailNotificationService,
        private ServerSuspensionEmailService $serverSuspensionEmailService,
    ) {}

    public function handle(): void
    {
       $this->handleServersToSuspend();
    }

    private function handleServersToSuspend(): void
    {
        $serversToSuspend = $this->serverRepository->getServersToSuspend(new \DateTime());
        foreach ($serversToSuspend as $server) {
            if ($this->tryToRenewServer($server)) {
                continue;
            }

            $server->setIsSuspended(true);
            $this->serverRepository->save($server);

            $this->pterodactylService->getApi()
                ->servers
                ->suspend($server->getPterodactylServerId());

            $this->serverSuspensionEmailService->sendServerSuspensionEmail($server);
        }
    }

    private function tryToRenewServer(Server $server): bool
    {
        if (!$server->isAutoRenewal()) {
            return false;
        }

        try {
            $selectedPrice = $server->getServerProduct()->getSelectedPrice();
            $slots = null;
            
            if ($selectedPrice->getType()->value === ProductPriceTypeEnum::SLOT->value) {
                $slots = $this->serverSlotPricingService->getServerSlots($server);
            }
            
            $this->storeService->validateUserBalanceByPrice(
                $server->getUser(),
                $selectedPrice,
                $slots
            );

            $this->renewServerService->renewServer($server, $server->getUser(), null, $slots);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
