<?php

namespace App\Core\Service\User;

use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\EmailVerificationValueEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Event\User\Registration\UserEmailVerificationRequestedEvent;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\UserRepository;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\Mailer\EmailVerificationService;
use App\Core\Service\SettingService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UserEmailService
{
    public function __construct(
        private MessageBusInterface      $messageBus,
        private LoggerInterface          $logger,
        private TranslatorInterface      $translator,
        private SettingService           $settingService,
        private EmailVerificationService $emailVerificationService,
        private EmailNotificationService $emailNotificationService,
        private UserRepository           $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function sendVerificationEmail(int $userId, string $email): void
    {
        try {
            $verificationMode = $this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
            
            if ($verificationMode === EmailVerificationValueEnum::DISABLED->value) {
                return;
            }

            $user = $this->userRepository->find($userId);
            if (!$user) {
                $this->logger->warning('User not found when attempting to send verification email', [
                    'userId' => $userId,
                ]);
                return;
            }

            $siteName = $this->settingService->getSetting(SettingEnum::SITE_TITLE->value);
            $siteUrl = $this->settingService->getSetting(SettingEnum::SITE_URL->value);
            
            $verificationToken = $this->emailVerificationService->createVerificationToken($user);
            $verificationUrl = sprintf('%s/verify-email?token=%s', $siteUrl, urlencode($verificationToken));

            $emailMessage = new SendEmailMessage(
                $email,
                $this->translator->trans('pteroca.email.registration.subject'),
                'email/registration.html.twig',
                [
                    'user' => $user,
                    'siteName' => $siteName,
                    'siteUrl' => $siteUrl,
                    'verificationUrl' => $verificationUrl,
                ]
            );
            $this->messageBus->dispatch($emailMessage);

            $this->emailNotificationService->logEmailSent(
                $user,
                EmailTypeEnum::REGISTRATION,
                null,
                $this->translator->trans('pteroca.email.registration.subject')
            );

            $this->emailNotificationService->logEmailSent(
                $user,
                EmailTypeEnum::EMAIL_VERIFICATION,
                null,
                $this->translator->trans('pteroca.email.verification.subject', ['%siteName%' => $siteName])
            );

            $verificationRequestedEvent = new UserEmailVerificationRequestedEvent(
                $userId,
                $email,
                $verificationToken
            );
            $this->eventDispatcher->dispatch($verificationRequestedEvent);
            
        } catch (Exception $exception) {
            $this->logger->error('Failed to send verification email', [
                'exception' => $exception->getMessage(),
                'userId' => $userId,
                'email' => $email,
            ]);
        }
    }
}
