<?php

namespace App\Core\Service\System;

use App\Core\DTO\PterodactylAddonVersionDTO;
use App\Core\DTO\SystemVersionDTO;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SystemVersionService
{
    private const VERSION_URL = 'https://pteroca.com/api/v1/version/current';

    private const CACHE_KEY = 'current_release_version';

    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly string $currentVersion,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    )
    {
    }

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function getVersionInformation(): SystemVersionDTO
    {
        $currentVersion = $this->getCurrentReleaseVersion();
        $pterodactylAddonVersion = new PterodactylAddonVersionDTO(
            latestVersion: $currentVersion['plugin']['version'],
            zipUrl: $currentVersion['plugin']['zipball_url'],
            tarUrl: $currentVersion['plugin']['tarball_url'],
            changelog: $currentVersion['plugin']['changelog'],
            releaseDate: new DateTime($currentVersion['plugin']['release_date']),
        );

        return new SystemVersionDTO(
            currentVersion: sprintf('v%s', $this->currentVersion),
            latestVersion: $currentVersion['version'],
            zipUrl: $currentVersion['zipball_url'],
            tarUrl: $currentVersion['tarball_url'],
            changelog: $currentVersion['changelog'],
            releaseDate: new DateTime($currentVersion['release_date']),
            pterodactylAddonVersion: $pterodactylAddonVersion,
        );
    }

    private function getCurrentReleaseVersion(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->httpClient
                ->request('GET', self::VERSION_URL)
                ->toArray();
        });
    }
}
