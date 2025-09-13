<?php

namespace App\Core\Service\Mailer;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Email\EmailVerificationContextDTO;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\EmailVerificationValueEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\SettingService;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailVerificationService
{
    private Configuration $jwtConfiguration;
    private const JWT_ISSUER = 'pteroca';
    private const RESEND_LIMIT_MINUTES = 5;
    private const JWT_TOKEN_LIFETIME_HOURS = 24;
    private const MINUTES_TO_SECONDS_MULTIPLIER = 60;

    public function __construct(
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly EmailNotificationService $emailNotificationService,
    ) {
        $this->jwtConfiguration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($_ENV['APP_SECRET']),
        );
    }

    public function sendVerificationEmail(UserInterface $user): void
    {
        $verificationMode = $this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
        
        if ($verificationMode === EmailVerificationValueEnum::DISABLED->value) {
            return;
        }

        $context = $this->buildEmailContext($user);
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.verification.subject', ['%siteName%' => $context->siteName]),
            'email/email_verification.html.twig',
            [
                'user' => $context->user,
                'verificationUrl' => $context->verificationUrl,
                'siteName' => $context->siteName,
                'siteUrl' => $context->siteUrl,
            ]
        );

        try {
            $this->messageBus->dispatch($emailMessage);
            
            $this->emailNotificationService->logEmailSent(
                $user,
                EmailTypeEnum::EMAIL_VERIFICATION,
                null,
                $this->translator->trans('pteroca.email.verification.subject', ['%siteName%' => $context->siteName])
            );
        } catch (\Exception $exception) {
            $this->logger->error('Failed to send verification email', [
                'exception' => $exception,
                'user' => $user,
            ]);
            throw $exception;
        }
    }

    public function canResendVerification(UserInterface $user): bool
    {
        $lastSentLog = $this->emailNotificationService->getLastEmailByType($user, EmailTypeEnum::EMAIL_VERIFICATION);
        
        if (!$lastSentLog) {
            return true;
        }

        $now = new DateTimeImmutable();
        $lastSentAt = DateTimeImmutable::createFromInterface($lastSentLog->getSentAt());
        $timeDiff = $now->getTimestamp() - $lastSentAt->getTimestamp();
        
        return $timeDiff >= (self::RESEND_LIMIT_MINUTES * self::MINUTES_TO_SECONDS_MULTIPLIER);
    }

    public function resendVerificationEmail(UserInterface $user): void
    {
        if (!$this->canResendVerification($user)) {
            throw new \RuntimeException(
                $this->translator->trans('pteroca.email.verification.resend_too_soon', [
                    '%minutes%' => self::RESEND_LIMIT_MINUTES
                ])
            );
        }

        $context = $this->buildEmailContext($user);
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.verification.subject', ['%siteName%' => $context->siteName]),
            'email/email_verification.html.twig',
            [
                'user' => $context->user,
                'verificationUrl' => $context->verificationUrl,
                'siteName' => $context->siteName,
                'siteUrl' => $context->siteUrl,
            ]
        );

        try {
            $this->messageBus->dispatch($emailMessage);
            
            $this->emailNotificationService->logEmailSent(
                $user,
                EmailTypeEnum::EMAIL_VERIFICATION,
                null,
                $this->translator->trans('pteroca.email.verification.subject', ['%siteName%' => $context->siteName])
            );
        } catch (\Exception $exception) {
            $this->logger->error('Failed to resend verification email', [
                'exception' => $exception,
                'user' => $user,
            ]);
            throw $exception;
        }
    }

    public function createVerificationToken(UserInterface $user): string
    {
        $now = new DateTimeImmutable();
        $token = $this->jwtConfiguration->builder()
            ->issuedBy(self::JWT_ISSUER)
            ->issuedAt($now)
            ->expiresAt($now->modify(sprintf('+%d hours', self::JWT_TOKEN_LIFETIME_HOURS)))
            ->withClaim('uid', $user->getId())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
            
        return $token->toString();
    }

    private function buildEmailContext(UserInterface $user): EmailVerificationContextDTO
    {
        $verificationToken = $this->createVerificationToken($user);
        $baseUrl = $this->settingService->getSetting(SettingEnum::SITE_URL->value);
        $verificationUrl = sprintf('%s/verify-email?token=%s', $baseUrl, urlencode($verificationToken));
        $siteName = $this->settingService->getSetting(SettingEnum::SITE_TITLE->value);

        return new EmailVerificationContextDTO(
            user: $user,
            verificationUrl: $verificationUrl,
            siteName: $siteName,
            siteUrl: $baseUrl,
        );
    }
}
