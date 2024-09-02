<?php

namespace App\Core\Tests\Unit\Service\Authorization;

use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserVerificationServiceTest extends TestCase
{
    private SettingService $settingService;
    private TranslatorInterface $translator;
    private UserVerificationService $userVerificationService;

    protected function setUp(): void
    {
        $this->settingService = $this->createMock(SettingService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->userVerificationService = new UserVerificationService(
            $this->settingService,
            $this->translator
        );
    }

    public function testValidateUserVerificationWhenVerificationIsRequiredAndUserIsVerified(): void
    {
        $user = $this->createMock(User::class);

        $this->settingService
            ->method('getSetting')
            ->with(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value)
            ->willReturn('1');

        $user->method('isVerified')
            ->willReturn(true);

        $this->userVerificationService->validateUserVerification($user);
        $this->assertTrue(true);
    }

    public function testValidateUserVerificationWhenVerificationIsRequiredAndUserIsNotVerified(): void
    {
        $user = $this->createMock(User::class);

        $this->settingService
            ->method('getSetting')
            ->with(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value)
            ->willReturn('1');

        $user->method('isVerified')
            ->willReturn(false);

        $this->translator
            ->method('trans')
            ->with('pteroca.system.email_not_verified')
            ->willReturn('Email not verified.');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email not verified.');

        $this->userVerificationService->validateUserVerification($user);
    }

    public function testValidateUserVerificationWhenVerificationIsNotRequired(): void
    {
        $user = $this->createMock(User::class);

        $this->settingService
            ->method('getSetting')
            ->with(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value)
            ->willReturn('0');

        $user->method('isVerified')
            ->willReturn(false);

        $this->userVerificationService->validateUserVerification($user);
        $this->assertTrue(true);
    }
}
