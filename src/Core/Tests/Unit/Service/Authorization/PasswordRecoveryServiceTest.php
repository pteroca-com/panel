<?php

namespace App\Core\Tests\Unit\Service\Authorization;

use App\Core\Entity\PasswordResetRequest;
use App\Core\Entity\User;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\PasswordResetRequestRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Authorization\PasswordRecoveryService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordRecoveryServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private PasswordResetRequestRepository $passwordResetRequestRepository;
    private MessageBusInterface $messageBus;
    private TranslatorInterface $translator;
    private SettingService $settingService;
    private UserPasswordHasherInterface $passwordHasher;
    private PasswordRecoveryService $passwordRecoveryService;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->passwordRecoveryService = new PasswordRecoveryService(
            $this->userRepository,
            $this->passwordResetRequestRepository,
            $this->messageBus,
            $this->translator,
            $this->settingService,
            $this->passwordHasher,
            $this->logger
        );
    }

    public function testCreateRecoveryRequestForExistingUserWithoutActiveRequest(): void
    {
        $user = $this->createMock(User::class);
        $email = 'user@example.com';

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->userRepository
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $this->passwordResetRequestRepository
            ->method('hasActiveRequest')
            ->with($user)
            ->willReturn(false);

        $this->passwordResetRequestRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(PasswordResetRequest::class));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new SendEmailMessage(
                $email,
                'subject',
                'email/reset_password.html.twig',
                ['recoveryUrl' => '']
            ), [new HandledStamp('result', 'handler_name')]));

        $this->passwordRecoveryService->createRecoveryRequest($email);
    }

    public function testCreateRecoveryRequestForNonExistingUser(): void
    {
        $email = 'nonexistent@example.com';

        $this->userRepository
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $this->passwordResetRequestRepository
            ->expects($this->never())
            ->method('hasActiveRequest');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->passwordRecoveryService->createRecoveryRequest($email);
    }

    public function testValidateRecoveryTokenWithValidToken(): void
    {
        $token = 'valid_token';
        $passwordResetRequest = $this->createMock(PasswordResetRequest::class);

        $this->passwordResetRequestRepository
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($passwordResetRequest);

        $passwordResetRequest
            ->method('getIsUsed')
            ->willReturn(false);

        $passwordResetRequest
            ->method('getExpiresAt')
            ->willReturn((new \DateTime())->modify('+1 hour'));

        $result = $this->passwordRecoveryService->validateRecoveryToken($token);

        $this->assertTrue($result);
    }

    public function testValidateRecoveryTokenWithInvalidToken(): void
    {
        $token = 'invalid_token';

        $this->passwordResetRequestRepository
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn(null);

        $result = $this->passwordRecoveryService->validateRecoveryToken($token);

        $this->assertFalse($result);
    }

    public function testUpdateUserPasswordWithValidToken(): void
    {
        $token = 'valid_token';
        $password = 'new_password';
        $user = $this->createMock(User::class);
        $passwordResetRequest = $this->createMock(PasswordResetRequest::class);

        $this->passwordResetRequestRepository
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($passwordResetRequest);

        $passwordResetRequest
            ->method('getUser')
            ->willReturn($user);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $password)
            ->willReturn('hashed_password');

        $user->expects($this->once())
            ->method('setPassword')
            ->with('hashed_password');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $passwordResetRequest
            ->expects($this->once())
            ->method('setIsUsed')
            ->with(true);

        $this->passwordResetRequestRepository
            ->expects($this->once())
            ->method('save')
            ->with($passwordResetRequest);

        $result = $this->passwordRecoveryService->updateUserPassword($token, $password);

        $this->assertTrue($result);
    }

    public function testUpdateUserPasswordWithInvalidToken(): void
    {
        $token = 'invalid_token';
        $password = 'new_password';

        $this->passwordResetRequestRepository
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn(null);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $result = $this->passwordRecoveryService->updateUserPassword($token, $password);

        $this->assertFalse($result);
    }
}
