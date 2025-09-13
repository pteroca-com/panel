<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\VoucherTypeEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Mailer\BoughtConfirmationEmailService;
use App\Core\Service\Product\ProductPriceCalculatorService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Voucher\VoucherPaymentService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RenewServerService extends AbstractActionServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerRepository $serverRepository,
        private readonly BoughtConfirmationEmailService $boughtConfirmationEmailService,
        private readonly EmailNotificationService $emailNotificationService,
        private readonly LogService $logService,
        private readonly VoucherPaymentService $voucherPaymentService,
        private readonly ServerSlotPricingService $serverSlotPricingService,
        UserRepository $userRepository,
        ProductPriceCalculatorService $productPriceCalculatorService,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ) {
        parent::__construct($userRepository, $pterodactylService, $voucherPaymentService, $productPriceCalculatorService, $translator, $logger);
    }

    public function renewServer(
        Server $server,
        UserInterface $user,
        ?string $voucherCode = null,
        ?int $slots = null
    ): void
    {
        if (!empty($voucherCode)) {
            $this->voucherPaymentService->validateVoucherCode(
                $voucherCode,
                $user,
                VoucherTypeEnum::SERVER_DISCOUNT,
            );
        }

        $currentExpirationDate = $server->getExpiresAt();
        if ($currentExpirationDate < new \DateTime()) {
            $currentExpirationDate = new \DateTime();
        } else {
            $currentExpirationDate = clone $currentExpirationDate;
        }

        $selectedPrice = $server->getServerProduct()->getSelectedPrice();
        
        if ($slots === null && $selectedPrice->getType()->value === ProductPriceTypeEnum::SLOT->value) {
            $slots = $this->serverSlotPricingService->getServerSlots($server);
        }
        
        if ($selectedPrice->getType() === ProductPriceTypeEnum::ON_DEMAND) {
            try {
                $pterodactylClientApi = $this->pterodactylClientService
                    ->getApi($user);
                $serverResources = $pterodactylClientApi->servers
                    ->resources($server->getPterodactylServerIdentifier());
            } catch (Exception) {
                $serverResources = null;
            }

            $isServerOffline = $serverResources === null || $serverResources->get('current_state') === 'offline';
            $chargeBalance = !$isServerOffline;
        } else {
            $chargeBalance = true;
        }

        $expirationDateModifier = sprintf('+%d %s', $selectedPrice->getValue(), $selectedPrice->getUnit()->value);
        $server->setExpiresAt($currentExpirationDate->modify($expirationDateModifier));
        if ($server->getIsSuspended()) {
            $this->pterodactylService->getApi()->servers->unsuspend($server->getPterodactylServerId());
            $server->setIsSuspended(false);
        }

        $this->serverRepository->save($server);
        if ($chargeBalance) {
            $this->updateUserBalance($user, $server->getServerProduct(), $selectedPrice->getId(), $voucherCode, $slots);
        }

        $previousExpiresAt = clone $currentExpirationDate;
        if ($this->boughtConfirmationEmailService->shouldSendRenewalNotification($server, $previousExpiresAt, $server->getExpiresAt())) {
            $this->boughtConfirmationEmailService->sendRenewConfirmationEmail(
                $user,
                $server,
                $this->getPterodactylAccountLogin($user),
            );
            
        }

        $this->logService->logAction(
            $user,
            LogActionEnum::RENEW_SERVER,
            ['server' => $server],
        );
    }
}
