<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylNestsInterface;
use App\Core\DTO\Pterodactyl\Application\PterodactylNest;
use App\Core\DTO\Pterodactyl\Collection;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PterodactylNests extends AbstractPterodactylApplicationAdapter implements PterodactylNestsInterface
{
    /**
     * Get a paginated collection of nests.
     *
     * @param array $query
     * @return Collection
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function paginate(array $query = []): Collection
    {
        $response = $this->makeRequest('GET', 'nests', [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to get nests: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        $nests = [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $nest) {
                $nests[] = new PterodactylNest($nest);
            }
        }

        return new Collection($nests, $this->getMetaFromResponse($data));
    }

    /**
     * Get all nests.
     *
     * @param array $query
     * @return Collection
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function all(array $query = []): Collection
    {
        // Dla metody all, pobieramy wszystkie nesty
        $query = array_merge($query, ['per_page' => 100]);
        return $this->paginate($query);
    }

    /**
     * Get a nest instance by id.
     *
     * @param int $nestId
     * @param array $query
     * @return PterodactylNest
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function get(int $nestId, array $query = []): PterodactylNest
    {
        $response = $this->makeRequest('GET', "nests/$nestId", [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to get nest: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        return new PterodactylNest($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

}
