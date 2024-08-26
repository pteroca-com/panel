<?php

namespace App\Core\Tests\Unit\Provider\Captcha;

use App\Core\Enum\SettingEnum;
use App\Core\Provider\Captcha\GoogleCaptchaProvider;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;

class GoogleCaptchaProviderTest extends TestCase
{
    private SettingService $settingService;

    protected function setUp(): void
    {
        $this->settingService = $this->createMock(SettingService::class);
    }

    public function testValidateCaptchaSuccess(): void
    {
        $captchaResponse = 'valid-captcha-response';
        $secretKey = 'valid-secret-key';
        $expectedUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse";

        $this->settingService
            ->method('getSetting')
            ->with(SettingEnum::GOOGLE_CAPTCHA_SECRET_KEY->value)
            ->willReturn($secretKey);

        $googleCaptchaProvider = $this->getMockBuilder(GoogleCaptchaProvider::class)
            ->setConstructorArgs([$this->settingService])
            ->onlyMethods(['fileGetContents'])
            ->getMock();

        $googleCaptchaProvider->expects($this->once())
            ->method('fileGetContents')
            ->with($expectedUrl)
            ->willReturn(json_encode(['success' => true]));

        $result = $googleCaptchaProvider->validateCaptcha($captchaResponse);

        $this->assertTrue($result);
    }

    public function testValidateCaptchaFailure(): void
    {
        $captchaResponse = 'invalid-captcha-response';
        $secretKey = 'valid-secret-key';
        $expectedUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse";

        $this->settingService
            ->method('getSetting')
            ->with(SettingEnum::GOOGLE_CAPTCHA_SECRET_KEY->value)
            ->willReturn($secretKey);

        $googleCaptchaProvider = $this->getMockBuilder(GoogleCaptchaProvider::class)
            ->setConstructorArgs([$this->settingService])
            ->onlyMethods(['fileGetContents'])
            ->getMock();

        $googleCaptchaProvider->expects($this->once())
            ->method('fileGetContents')
            ->with($expectedUrl)
            ->willReturn(json_encode(['success' => false]));

        $result = $googleCaptchaProvider->validateCaptcha($captchaResponse);

        $this->assertFalse($result);
    }
}
