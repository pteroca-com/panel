<?php

namespace App\Core\Service\Marketplace;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MarketplaceService
{
    private const MARKETPLACE_API = 'https://marketplace.pteroca.com/api/v1/products';
    private const CACHE_TTL = 21600; // 6 hours
    private const ALLOWED_SORTS = ['newest', 'popular', 'rating'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {}

    public function getProducts(int $page, int $limit, string $sort, string $tags = ''): array
    {
        $sort = in_array($sort, self::ALLOWED_SORTS) ? $sort : 'newest';
        $page = max(1, $page);
        $limit = min(30, max(1, $limit));

        $cacheKey = sprintf('marketplace_products_%d_%s_%d_%s', $page, $sort, $limit, md5($tags));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($page, $limit, $sort, $tags): array {
            $item->expiresAfter(self::CACHE_TTL);

            $query = ['page' => $page, 'limit' => $limit, 'sort' => $sort];
            if ($tags !== '') {
                $query['tags'] = $tags;
            }

            $response = $this->httpClient->request('GET', self::MARKETPLACE_API, [
                'query' => $query,
                'timeout' => 5,
            ]);

            return $response->toArray();
        });
    }
}
