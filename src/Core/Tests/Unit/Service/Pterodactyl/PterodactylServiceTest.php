<?php

namespace App\Core\Tests\Unit\Service\Pterodactyl;

use App\Core\Enum\SettingEnum;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\PterodactylApi;

class PterodactylServiceTest extends TestCase
{
    private SettingService $settingService;
    private TranslatorInterface $translator;
    private PterodactylService $pterodactylService;

    protected function setUp(): void
    {
        $this->settingService = $this->createMock(SettingService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->pterodactylService = new PterodactylService($this->settingService, $this->translator);
    }

    public function testGetApiReturnsPterodactylApiInstance(): void
    {
        $this->settingService
            ->method('getSetting')
            ->willReturnMap([
                [SettingEnum::PTERODACTYL_PANEL_URL->value, 'https://panel.example.com'],
                [SettingEnum::PTERODACTYL_API_KEY->value, 'api_key'],
            ]);

        $api = $this->pterodactylService->getApi();
        $this->assertInstanceOf(PterodactylApi::class, $api);
    }

    public function testGetApiThrowsExceptionWhenCredentialsAreMissing(): void
    {
        $this->settingService
            ->method('getSetting')
            ->willReturnMap([
                [SettingEnum::PTERODACTYL_PANEL_URL->value, ''],
                [SettingEnum::PTERODACTYL_API_KEY->value, ''],
            ]);

        $this->translator
            ->method('trans')
            ->with('pteroca.system.pterodactyl_error')
            ->willReturn('Pterodactyl configuration error.');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pterodactyl configuration error.');

        $this->pterodactylService->getApi();
    }

    public function testGetApiReusesExistingApiInstance(): void
    {
        $this->settingService
            ->method('getSetting')
            ->willReturnMap([
                [SettingEnum::PTERODACTYL_PANEL_URL->value, 'https://panel.example.com'],
                [SettingEnum::PTERODACTYL_API_KEY->value, 'api_key'],
            ]);

        $apiFirstCall = $this->pterodactylService->getApi();
        $apiSecondCall = $this->pterodactylService->getApi();

        $this->assertSame($apiFirstCall, $apiSecondCall);
    }
}
