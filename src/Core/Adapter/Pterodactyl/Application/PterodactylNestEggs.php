<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylNestEggsInterface;
use App\Core\DTO\Pterodactyl\Collection;
use App\Core\DTO\Pterodactyl\Resource;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PterodactylNestEggs extends AbstractPterodactylApplicationAdapter implements PterodactylNestEggsInterface
{
    /**
     * Get eggs for a specific nest.
     *
     * @param int $nestId
     * @param array $query
     * @return Collection
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getEggs(int $nestId, array $query = []): Collection
    {
        return $this->paginate($nestId, $query);
    }

    /**
     * Get a specific egg from a nest.
     *
     * @param int $nestId
     * @param int $eggId
     * @param array $query
     * @return Resource
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function getEgg(int $nestId, int $eggId, array $query = []): Resource
    {
        $response = $this->makeRequest('GET', "nests/$nestId/eggs/$eggId", [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to get nest egg: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        return new Resource($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    /**
     * Get a paginated collection of eggs for a specific nest.
     *
     * @param int $nestId
     * @param array $query
     * @return Collection
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function paginate(int $nestId, array $query = []): Collection
    {
        $response = $this->makeRequest('GET', "nests/$nestId/eggs", [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to get nest eggs: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        $eggs = [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $egg) {
                $eggs[] = new Resource($egg);
            }
        }

        return new Collection($eggs, $this->getMetaFromResponse($data));
    }

    /**
     * Get all eggs for a specific nest.
     *
     * @param int $nestId
     * @param array $query
     * @return Collection
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function all(int $nestId, array $query = []): Collection
    {
        // Dla metody all, pobieramy wszystkie eggs
        $query = array_merge($query, ['per_page' => 100]);
        return $this->paginate($nestId, $query);
    }
}
