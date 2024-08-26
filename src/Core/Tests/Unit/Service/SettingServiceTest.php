<?php

namespace App\Core\Tests\Unit\Service;

use App\Core\Entity\Setting;
use App\Core\Repository\SettingRepository;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SettingServiceTest extends TestCase
{
    private SettingRepository $settingRepository;
    private CacheInterface $cache;
    private SettingService $settingService;

    protected function setUp(): void
    {
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->settingService = new SettingService(
            $this->settingRepository,
            $this->cache
        );
    }

    public function testGetSettingFromCache(): void
    {
        $this->cache
            ->method('get')
            ->with('app_setting_site_name')
            ->willReturn('My Site');

        $result = $this->settingService->getSetting('site_name');

        $this->assertEquals('My Site', $result);
    }

    public function testGetSettingFromRepositoryWhenNotInCache(): void
    {
        $setting = new Setting();
        $setting->setValue('My Site');

        $this->settingRepository
            ->method('findOneBy')
            ->with(['name' => 'site_name'])
            ->willReturn($setting);

        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem->method('expiresAfter')->with(SettingService::CACHE_TTL);

        $this->cache
            ->method('get')
            ->with('app_setting_site_name')
            ->willReturnCallback(function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $result = $this->settingService->getSetting('site_name');

        $this->assertEquals('My Site', $result);
    }

    public function testSaveSettingInCache(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('app_setting_site_name');

        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem->method('expiresAfter')->with(SettingService::CACHE_TTL);

        $this->cache
            ->method('get')
            ->with('app_setting_site_name')
            ->willReturnCallback(function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $this->settingService->saveSettingInCache('site_name', 'New Value');
    }

    public function testDeleteSettingFromCache(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('app_setting_site_name');

        $this->settingService->deleteSettingFromCache('site_name');
    }
}
