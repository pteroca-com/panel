<?php

namespace App\Core\Service\Mailer;

use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\User;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\SettingService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class AdminServerCreationEmailService
{
    public function __construct(
        private MessageBusInterface      $messageBus,
        private EmailNotificationService $emailNotificationService,
        private SettingService           $settingService,
        private TranslatorInterface      $translator,
    ) {}

    public function sendAdminServerCreationEmail(
        User $user,
        Server $server,
        ServerProduct $serverProduct,
        bool $isFreeServer,
    ): void {
        $context = [
            'user'         => $user,
            'server'       => ['name' => $server->getName(), 'expiresAt' => $server->getExpiresAt()],
            'product'      => ['name' => $serverProduct->getName()],
            'panel'        => ['url' => $this->settingService->getSetting(SettingEnum::SITE_URL->value)],
            'isFreeServer' => $isFreeServer,
        ];

        $subject = $this->translator->trans('pteroca.email.admin_server_created.subject');

        $this->messageBus->dispatch(new SendEmailMessage(
            $user->getEmail(),
            $subject,
            'email/admin_server_creation.html.twig',
            $context
        ));

        $this->emailNotificationService->logEmailSent(
            $user,
            EmailTypeEnum::ADMIN_SERVER_CREATED,
            $server,
            $subject,
            [
                'server_name'    => $server->getName(),
                'expires_at'     => $server->getExpiresAt()->format('Y-m-d H:i:s'),
                'is_free_server' => $isFreeServer,
            ]
        );
    }
}
