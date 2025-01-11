<?php

namespace App\Core\Service\Mailer;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
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
    ) {}

    public function sendBoughtConfirmationEmail(
        User $user,
        Product $product,
        Server $server,
        string $pterodactylAccountUsername,
    ): void {
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
    }

    public function sendRenewConfirmationEmail(User $user, Product $product, Server $server, string $pterodactylAccountUsername): void
    {
        $serverDetails = $this->serverService->getServerDetails($server);

        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.renew.subject'),
            'email/renew_product.html.twig',
            [
                'user' => $user,
                'product' => $product,
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
    }

    private function getClientPanelUrl(): string
    {
        return $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value)
            ? $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value)
            : $this->settingService->getSetting(SettingEnum::SITE_URL->value);
    }
}
