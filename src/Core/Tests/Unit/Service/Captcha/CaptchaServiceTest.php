<?php

namespace App\Core\Tests\Unit\Service\Captcha;

use App\Core\Enum\SettingEnum;
use App\Core\Provider\Captcha\CaptchaProviderInterface;
use App\Core\Service\Captcha\CaptchaService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;

class CaptchaServiceTest extends TestCase
{
    public function testIsCaptchaEnabledWhenEnabled(): void
    {
        $settingService = $this->createMock(SettingService::class);
        $captchaProvider = $this->createMock(CaptchaProviderInterface::class);

        $settingService->method('getSetting')
            ->with(SettingEnum::GOOGLE_CAPTCHA_VERIFICATION->value)
            ->willReturn('1');

        $captchaService = new CaptchaService($settingService, $captchaProvider);

        $this->assertTrue($captchaService->isCaptchaEnabled());
    }

    public function testIsCaptchaEnabledWhenDisabled(): void
    {
        $settingService = $this->createMock(SettingService::class);
        $captchaProvider = $this->createMock(CaptchaProviderInterface::class);

        $settingService->method('getSetting')
            ->with(SettingEnum::GOOGLE_CAPTCHA_VERIFICATION->value)
            ->willReturn('0');

        $captchaService = new CaptchaService($settingService, $captchaProvider);

        $this->assertFalse($captchaService->isCaptchaEnabled());
    }

    public function testValidateCaptcha(): void
    {
        $settingService = $this->createMock(SettingService::class);
        $captchaProvider = $this->createMock(CaptchaProviderInterface::class);

        $captchaResponse = 'test-captcha-response';

        $captchaProvider->method('validateCaptcha')
            ->with($captchaResponse)
            ->willReturn(true);

        $captchaService = new CaptchaService($settingService, $captchaProvider);

        $this->assertTrue($captchaService->validateCaptcha($captchaResponse));
    }

    public function testValidateCaptchaFails(): void
    {
        $settingService = $this->createMock(SettingService::class);
        $captchaProvider = $this->createMock(CaptchaProviderInterface::class);

        $captchaResponse = 'test-captcha-response';

        $captchaProvider->method('validateCaptcha')
            ->with($captchaResponse)
            ->willReturn(false);

        $captchaService = new CaptchaService($settingService, $captchaProvider);

        $this->assertFalse($captchaService->validateCaptcha($captchaResponse));
    }
}

