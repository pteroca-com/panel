<?php

namespace App\Core\Service\Mailer;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Email\ServerSuspensionContextDTO;
use App\Core\Entity\Server;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\SettingService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerSuspensionEmailService
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly EmailNotificationService $emailNotificationService,
        private readonly ServerService $serverService,
    ) {}

    public function sendServerSuspensionEmail(Server $server): void
    {
        $context = $this->buildEmailContext($server);
        $emailMessage = new SendEmailMessage(
            $server->getUser()->getEmail(),
            $this->translator->trans('pteroca.email.suspended.subject'),
            'email/server_suspended.html.twig',
            [
                'user' => $context->user,
                'server' => $context->server,
                'serverName' => $context->serverName,
                'suspensionDate' => $context->suspensionDate,
                'siteName' => $context->siteName,
                'siteUrl' => $context->siteUrl,
                'panelUrl' => $context->panelUrl,
                'autoDeleteEnabled' => $context->autoDeleteEnabled,
                'deleteAfterDays' => $context->deleteAfterDays,
                'deleteDate' => $context->deleteDate,
            ]
        );

        try {
            $this->messageBus->dispatch($emailMessage);
            
            $this->emailNotificationService->logEmailSent(
                $server->getUser(),
                EmailTypeEnum::SERVER_SUSPENDED,
                $server,
                $this->translator->trans('pteroca.email.suspended.subject'),
                [
                    'server_name' => $context->serverName,
                    'suspension_date' => $context->suspensionDate->format('Y-m-d H:i:s'),
                    'auto_delete_enabled' => $context->autoDeleteEnabled,
                    'delete_after_days' => $context->deleteAfterDays,
                ]
            );
        } catch (\Exception $exception) {
            $this->logger->error('Failed to send server suspension email', [
                'exception' => $exception,
                'user' => $server->getUser(),
                'server' => $server,
            ]);
            throw $exception;
        }
    }

    private function buildEmailContext(Server $server): ServerSuspensionContextDTO
    {
        $suspensionDate = new DateTimeImmutable();
        $serverDetails = $this->serverService->getServerDetails($server);
        $serverName = $serverDetails?->name ?? 'N/A';
        $siteName = $this->settingService->getSetting(SettingEnum::SITE_TITLE->value);
        $siteUrl = $this->settingService->getSetting(SettingEnum::SITE_URL->value);
        $panelUrl = $this->getClientPanelUrl();
        
        $autoDeleteEnabled = (bool) $this->settingService->getSetting(
            SettingEnum::DELETE_SUSPENDED_SERVERS_ENABLED->value
        );
        
        $deleteAfterDays = null;
        $deleteDate = null;
        
        if ($autoDeleteEnabled) {
            $deleteAfterDays = (int) $this->settingService->getSetting(
                SettingEnum::DELETE_SUSPENDED_SERVERS_DAYS_AFTER->value
            );
            
            if ($deleteAfterDays > 0) {
                $deleteDate = $suspensionDate->modify(sprintf('+%d days', $deleteAfterDays));
            }
        }

        return new ServerSuspensionContextDTO(
            user: $server->getUser(),
            server: $server,
            serverName: $serverName,
            suspensionDate: $suspensionDate,
            siteName: $siteName,
            siteUrl: $siteUrl,
            panelUrl: $panelUrl,
            autoDeleteEnabled: $autoDeleteEnabled,
            deleteAfterDays: $deleteAfterDays,
            deleteDate: $deleteDate,
        );
    }

    private function getClientPanelUrl(): string
    {
        return $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value)
            ? $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value)
            : $this->settingService->getSetting(SettingEnum::SITE_URL->value);
    }
}
