<?php

namespace App\Core\Service\Authorization;

use App\Core\Entity\PasswordResetRequest;
use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\PasswordResetRequestRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\SettingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\ByteString;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordRecoveryService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordResetRequestRepository $passwordResetRequestRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
        private readonly SettingService $settingService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
    ) {}

    public function createRecoveryRequest(string $email): void
    {
        $userAccount = $this->userRepository->findOneBy(['email' => $email]);
        if (empty($userAccount)) {
            return;
        }

        if ($this->passwordResetRequestRepository->hasActiveRequest($userAccount)) {
            return;
        }

        $token = ByteString::fromRandom(32)->toString();
        $this->saveRecoveryRequest($userAccount, $token);
        $this->sendRecoveryEmail($userAccount, $token);
    }

    public function validateRecoveryToken(string $token): bool
    {
        $passwordResetRequest = $this->passwordResetRequestRepository->findOneBy(['token' => $token]);
        if (empty($passwordResetRequest) || $passwordResetRequest->getIsUsed()) {
            return false;
        }

        if ($passwordResetRequest->getExpiresAt() < new \DateTime()) {
            return false;
        }

        return true;
    }

    public function updateUserPassword(string $token, string $password): bool
    {
        $passwordResetRequest = $this->passwordResetRequestRepository->findOneBy(['token' => $token]);
        if (empty($passwordResetRequest)) {
            return false;
        }

        $user = $passwordResetRequest->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->userRepository->save($user);

        $passwordResetRequest->setIsUsed(true);
        $this->passwordResetRequestRepository->save($passwordResetRequest);
        return true;
    }

    private function saveRecoveryRequest(User $user, string $token): void
    {
        $passwordResetRequest = new PasswordResetRequest();
        $passwordResetRequest->setUser($user);
        $passwordResetRequest->setToken($token);
        $passwordResetRequest->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $this->passwordResetRequestRepository->save($passwordResetRequest);
    }

    private function sendRecoveryEmail(User $user, string $token): void
    {
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.recovery.subject'),
            'email/reset_password.html.twig',
            ['recoveryUrl' => $this->generateRecoveryUrl($token), 'user' => $user],
        );
        try {
            $this->messageBus->dispatch($emailMessage);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send recovery email', ['exception' => $e]);
        }
    }

    private function generateRecoveryUrl(string $token): string
    {
        return sprintf(
            '%s/reset-password/%s',
            $this->settingService->getSetting(SettingEnum::SITE_URL->value),
            $token,
        );
    }
}