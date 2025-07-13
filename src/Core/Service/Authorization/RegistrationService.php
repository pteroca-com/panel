<?php

namespace App\Core\Service\Authorization;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Action\Result\RegisterUserActionResult;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\UserRepository;
use App\Core\Service\Logs\LogService;
use App\Core\Service\SettingService;
use App\Core\Service\User\UserService;
use DateTimeImmutable;
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
        $verificationToken = $this->createVerificationToken($user);
        $baseUrl = $this->settingService->getSetting(SettingEnum::SITE_URL->value);
        $verificationUrl = sprintf('%s/verify-email/%s', $baseUrl, $verificationToken);
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.registration.subject'),
            'email/registration.html.twig',
            [
                'name' => $user->getName(),
                'verificationUrl' => $verificationUrl,
                'user' => $user,
            ]
        );
        try {
            $this->messageBus->dispatch($emailMessage);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to send registration email', [
                'exception' => $exception,
                'user' => $user,
            ]);
        }
    }

    private function createVerificationToken(UserInterface $user): string
    {
        $now = new DateTimeImmutable();
        $token = $this->jwtConfiguration->builder()
            ->issuedBy(self::JWT_ISSUER)
            ->issuedAt($now)
            ->withClaim('uid', $user->getId())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
        return $token->toString();
    }

    public function userExists(string $email): bool
    {
        return !empty($this->userRepository->findOneBy(['email' => $email]));
    }
}
