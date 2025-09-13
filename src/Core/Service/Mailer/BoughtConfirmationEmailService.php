<?php

namespace App\Core\Service\Mailer;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Product;
use App\Core\Entity\ProductPrice;
use App\Core\Entity\Server;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\SettingService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BoughtConfirmationEmailService
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly ServerService $serverService,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    public function sendBoughtConfirmationEmail(
        UserInterface $user,
        Server $server,
        Product $product,
        int $priceId,
        string $pterodactylAccountUsername,
    ): void {
        $price = $product->getPrices()->filter(
            fn(ProductPrice $price) => $price->getId() === $priceId
        )->first();

        if (empty($price)) {
            throw new \InvalidArgumentException('Price not found');
        }

        $serverDetails = $this->serverService->getServerDetails($server);
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.store.subject'),
            'email/purchased_product.html.twig',
            [
                'user' => $user,
                'product' => $product,
                'selectedPrice' => $price,
                'currency' => $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                'server' => [
                    'ip' => $serverDetails->ip,
                    'expiresAt' => $server->getExpiresAt()->format('Y-m-d H:i'),
                ],
                'panel' => [
                    'url' => $this->getClientPanelUrl(),
                    'username' => $pterodactylAccountUsername,
                ],
            ]
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
            ]
        );
    }

    public function sendRenewConfirmationEmail(
        UserInterface $user,
        Server $server,
        string $pterodactylAccountUsername
    ): void {
        $serverDetails = $this->serverService->getServerDetails($server);
        $product = $server->getServerProduct();
        $selectedPrice = $product->getSelectedPrice();

        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.renew.subject'),
            'email/renew_product.html.twig',
            [
                'user' => $user,
                'product' => $product,
                'selectedPrice' => $selectedPrice,
                'currency' => $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                'server' => [
                    'ip' => $serverDetails->ip,
                    'expiresAt' => $server->getExpiresAt()->format('Y-m-d H:i'),
                ],
                'panel' => [
                    'url' => $this->getClientPanelUrl(),
                    'username' => $pterodactylAccountUsername,
                ],
            ]
        );

        $this->messageBus->dispatch($emailMessage);
        
        $this->emailNotificationService->logEmailSent(
            $user,
            EmailTypeEnum::RENEW_PRODUCT,
            $server,
            $this->translator->trans('pteroca.email.renew.subject'),
            [
                'product_id' => $product->getId(),
                'price_type' => $selectedPrice->getType()->value,
                'server_expires_at' => $server->getExpiresAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    private function getClientPanelUrl(): string
    {
        return $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value)
            ? $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value)
            : $this->settingService->getSetting(SettingEnum::SITE_URL->value);
    }
}
