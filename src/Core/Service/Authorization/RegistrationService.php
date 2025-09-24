<?php

namespace App\Core\Service\Authorization;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Action\Result\RegisterUserActionResult;
use App\Core\DTO\Email\RegistrationEmailContextDTO;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\EmailVerificationValueEnum;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\UserRepository;
use App\Core\Service\Email\EmailNotificationService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Mailer\EmailVerificationService;
use App\Core\Service\SettingService;
use App\Core\Service\User\UserService;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationService
{
    private Configuration $jwtConfiguration;

    private const JWT_ISSUER = 'pteroca';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly LogService $logService,
        private readonly SettingService $settingService,
        private readonly UserService $userService,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly EmailNotificationService $emailNotificationService,
    ) {
        $this->jwtConfiguration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($_ENV['APP_SECRET']),
        );
    }

    public function registerUser(
        UserInterface $user,
        string $plainPassword,
        array $roles = [UserRoleEnum::ROLE_USER->name],
        bool $isVerified = false,
        bool $sendEmail = true
    ): RegisterUserActionResult
    {
        $existingDeletedUser = $this->userRepository->findDeletedByEmail($user->getEmail());
        
        if ($existingDeletedUser) {
            return $this->reactivateUser($existingDeletedUser, $plainPassword, $roles, $isVerified, $sendEmail);
        }

        $user->setIsVerified($isVerified);
        $user->setRoles($roles);

        try {
            $this->userService->createUserWithPterodactylAccount($user, $plainPassword);
        } catch (\Exception $exception) {
            return new RegisterUserActionResult(
                success: false,
                error: $exception->getMessage(),
            );
        }

        $this->userRepository->save($user);
        $this->logService->logAction($user, LogActionEnum::USER_REGISTERED);

        if ($sendEmail) {
            $this->sendRegistrationEmail($user);
        }

        return new RegisterUserActionResult(
            success: true,
            user: $user,
        );
    }

    private function reactivateUser(
        UserInterface $deletedUser,
        string $plainPassword,
        array $roles,
        bool $isVerified,
        bool $sendEmail
    ): RegisterUserActionResult
    {
        try {
            $deletedUser->restore();
            $deletedUser->setIsVerified($isVerified);
            $deletedUser->setRoles($roles);
            
            if (!empty($plainPassword)) {
                $deletedUser->setPlainPassword($plainPassword);
                $this->userService->updateUserInPterodactyl($deletedUser, $plainPassword);
            }

            $this->userRepository->save($deletedUser);
            $this->logService->logAction($deletedUser, LogActionEnum::USER_REGISTERED);

            if ($sendEmail) {
                $this->sendRegistrationEmail($deletedUser);
            }

            return new RegisterUserActionResult(
                success: true,
                user: $deletedUser,
            );
        } catch (\Exception $exception) {
            return new RegisterUserActionResult(
                success: false,
                error: $exception->getMessage(),
            );
        }
    }

    public function verifyEmail(string $token): void
    {
        try {
            $token = $this->jwtConfiguration->parser()->parse($token);
        } catch (\Exception) {
            throw new \RuntimeException($this->translator->trans('pteroca.register.verification_token_invalid'));
        }

        assert($token instanceof Plain);
        $constraints = [
            new IssuedBy(self::JWT_ISSUER),
        ];

        if (!$this->jwtConfiguration->validator()->validate($token, ...$constraints)) {
            throw new \RuntimeException($this->translator->trans('pteroca.register.verification_token_invalid'));
        }

        if ($token->claims()->has('exp')) {
            $expiry = $token->claims()->get('exp');
            $expiryTimestamp = $expiry instanceof \DateTimeInterface ? $expiry->getTimestamp() : (int) $expiry;
            if ($expiryTimestamp < time()) {
                throw new \RuntimeException($this->translator->trans('pteroca.register.verification_token_invalid'));
            }
        }

        if (!$this->jwtConfiguration->signer()->verify(
            $token->signature()->hash(),
            $token->payload(),
            $this->jwtConfiguration->signingKey()
        )) {
            throw new \RuntimeException($this->translator->trans('pteroca.register.verification_token_invalid'));
        }

        $userId = $token->claims()->get('uid');
        $user = $this->userRepository->find($userId);
        if (empty($user) || $user->isVerified()) {
            throw new \RuntimeException($this->translator->trans('pteroca.register.verification_token_invalid'));
        }

        $user->setIsVerified(true);
        $this->userRepository->save($user);
        $this->logService->logAction($user, LogActionEnum::USER_VERIFY_EMAIL);
    }

    private function sendRegistrationEmail(UserInterface $user): void
    {
        try {
            $context = $this->buildRegistrationEmailContext($user);
            
            $emailMessage = new SendEmailMessage(
                $user->getEmail(),
                $this->translator->trans('pteroca.email.registration.subject'),
                'email/registration.html.twig',
                [
                    'user' => $context->user,
                    'siteName' => $context->siteName,
                    'siteUrl' => $context->siteUrl,
                    'verificationUrl' => $context->verificationUrl,
                ]
            );
            
            $this->messageBus->dispatch($emailMessage);
            
            $this->emailNotificationService->logEmailSent(
                $user,
                EmailTypeEnum::REGISTRATION,
                null,
                $this->translator->trans('pteroca.email.registration.subject')
            );
            
            if ($context->verificationUrl !== null) {
                $this->emailNotificationService->logEmailSent(
                    $user,
                    EmailTypeEnum::EMAIL_VERIFICATION,
                    null,
                    $this->translator->trans('pteroca.email.verification.subject', ['%siteName%' => $context->siteName])
                );
            }
        } catch (\Exception $exception) {
            $this->logger->error('Failed to send registration email', [
                'exception' => $exception,
                'user' => $user,
            ]);
        }
    }

    private function buildRegistrationEmailContext(UserInterface $user): RegistrationEmailContextDTO
    {
        $verificationMode = $this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
        $siteName = $this->settingService->getSetting(SettingEnum::SITE_TITLE->value);
        $siteUrl = $this->settingService->getSetting(SettingEnum::SITE_URL->value);
        
        $verificationUrl = null;
        if ($verificationMode !== EmailVerificationValueEnum::DISABLED->value) {
            $verificationToken = $this->emailVerificationService->createVerificationToken($user);
            $verificationUrl = sprintf('%s/verify-email?token=%s', $siteUrl, urlencode($verificationToken));
        }
        
        return new RegistrationEmailContextDTO(
            user: $user,
            siteName: $siteName,
            siteUrl: $siteUrl,
            verificationUrl: $verificationUrl,
        );
    }


    public function userExists(string $email): bool
    {
        return !empty($this->userRepository->findOneBy(['email' => $email]));
    }

    public function getEmailVerificationMode(): string
    {
        return EmailVerificationValueEnum::tryFrom(
            $this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value)
        )?->value ?? EmailVerificationValueEnum::DISABLED->value;
    }
}
