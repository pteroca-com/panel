<?php

namespace App\Core\Service;

use App\Core\Repository\SettingRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class SettingService
{
    public const CACHE_TTL = 86400;

    public function __construct(
        private SettingRepository $settingRepository,
        private CacheInterface $cache,
    ) {}

    public function getSetting(string $name): ?string
    {
        return $this->cache->get(
            $this->createSettingCacheKey($name),
            function (ItemInterface $item) use ($name) {
                $item->expiresAfter(self::CACHE_TTL);
                $setting = $this->settingRepository->findOneBy(['name' => $name]);
                return $setting?->getValue();
            }
        );
    }

    public function saveSettingInCache(string $name, string $value): void
    {
        $settingKey = $this->createSettingCacheKey($name);
        $this->deleteSettingFromCache($name);
        $this->cache->get(
            $settingKey,
            function (ItemInterface $item) use ($value) {
                $item->expiresAfter(self::CACHE_TTL);
                return $value;
            }
        );
    }

    public function deleteSettingFromCache(string $name): void
    {
        $this->cache->delete($this->createSettingCacheKey($name));
    }

    private function createSettingCacheKey(string $name): string
    {
        return sprintf('app_setting_%s', $name);
    }
}