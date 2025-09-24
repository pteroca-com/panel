<?php

namespace App\Core\Service\Mailer;

use App\Core\Entity\Server;
use App\Core\Entity\Product;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Contract\UserInterface;
use App\Core\Service\SettingService;
use App\Core\Message\SendEmailMessage;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Service\Email\EmailNotificationService;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Core\Service\Email\EmailContextBuilderService;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Core\Exception\Email\ProductPriceNotFoundException;

class BoughtConfirmationEmailService
{
    public function __construct(
        private readonly EmailContextBuilderService $emailContextBuilder,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        private readonly EmailNotificationService $emailNotificationService,
        private readonly SettingService $settingService,
    ) {}

    public function sendBoughtConfirmationEmail(
        UserInterface $user,
        Server $server,
        Product $product,
        int $priceId,
        string $pterodactylAccountUsername,
        ?int $slots = null,
    ): void {
        $price = $product->findPriceById($priceId);

        if ($price === null) {
            throw ProductPriceNotFoundException::forPriceAndProduct($priceId, $product->getId());
        }

        $context = $this->emailContextBuilder->buildPurchaseContext(
            $user,
            $server,
            $product,
            $price,
            $pterodactylAccountUsername,
            $slots
        );

        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.store.subject'),
            'email/purchased_product.html.twig',
            $context->toArray()
        );

        $this->messageBus->dispatch($emailMessage);

        $this->emailNotificationService->logEmailSent(
            $user,
            EmailTypeEnum::PURCHASED_PRODUCT,
            $server,
            $this->translator->trans('pteroca.email.store.subject'),
            [
                'product_id' => $product->getId(),
                'price_id' => $priceId,
                'server_expires_at' => $server->getExpiresAt()->format('Y-m-d H:i:s'),
                'context' => $context->toArray(),
            ]
        );
    }

    public function sendRenewConfirmationEmail(
        UserInterface $user,
        Server $server,
        string $pterodactylAccountUsername
    ): void {
        $serverProduct = $server->getServerProduct();
        $selectedPrice = $serverProduct->getSelectedPrice();

        if ($selectedPrice === null) {
            throw ProductPriceNotFoundException::forPriceAndProduct(0, $serverProduct->getId());
        }

        $context = $this->emailContextBuilder->buildRenewalContext(
            $user,
            $server,
            $serverProduct,
            $selectedPrice,
            $pterodactylAccountUsername
        );

        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.renew.subject'),
            'email/renew_product.html.twig',
            $context->toArray()
        );

        $this->messageBus->dispatch($emailMessage);

        $this->emailNotificationService->logEmailSent(
            $user,
            EmailTypeEnum::RENEW_PRODUCT,
            $server,
            $this->translator->trans('pteroca.email.renew.subject'),
            [
                'product_id' => $server->getServerProduct()->getId(),
                'price_type' => $selectedPrice->getType()->value,
                'server_expires_at' => $server->getExpiresAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function shouldSendRenewalNotification(
        Server $server,
        \DateTimeInterface $previousExpiresAt,
        \DateTimeInterface $newExpiresAt
    ): bool {
        if (!$this->isRenewalNotificationEnabled()) {
            return false;
        }

        $lastNotification = $this->emailNotificationService->getLastEmailByType(
            $server->getUser(),
            EmailTypeEnum::RENEW_PRODUCT
        );

        if (!$this->hasMinimumTimePassed($lastNotification)) {
            return false;
        }

        return $this->isRenewalSignificant($server, $previousExpiresAt, $newExpiresAt);
    }

    private function isRenewalNotificationEnabled(): bool
    {
        return (bool) $this->settingService->getSetting(
            SettingEnum::RENEWAL_NOTIFICATION_ENABLED->value
        );
    }

    private function hasMinimumTimePassed($lastNotification): bool
    {
        if ($lastNotification === null) {
            return true;
        }

        $minPeriodHours = (int) $this->settingService->getSetting(
            SettingEnum::RENEWAL_NOTIFICATION_MIN_PERIOD_HOURS->value
        );

        $minDateTime = new \DateTime();
        $minDateTime->sub(new \DateInterval(sprintf('PT%dH', $minPeriodHours)));

        return $lastNotification->getSentAt() < $minDateTime;
    }

    private function isRenewalSignificant(
        Server $server,
        \DateTimeInterface $previousExpiresAt,
        \DateTimeInterface $newExpiresAt
    ): bool {
        $selectedPrice = $server->getServerProduct()->getSelectedPrice();
        $renewalPeriod = $previousExpiresAt->diff($newExpiresAt);

        if ($selectedPrice->getType() === ProductPriceTypeEnum::ON_DEMAND) {
            $minOnDemandHours = (int) $this->settingService->getSetting(
                SettingEnum::RENEWAL_NOTIFICATION_ON_DEMAND_MIN_HOURS->value
            );

            $totalHours = $renewalPeriod->days * 24 + $renewalPeriod->h;
            return $totalHours >= $minOnDemandHours;
        }

        return true;
    }
}
