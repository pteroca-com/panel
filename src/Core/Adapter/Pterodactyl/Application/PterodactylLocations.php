<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylLocationsInterface;
use App\Core\DTO\Pterodactyl\Application\PterodactylLocation;
use App\Core\DTO\Pterodactyl\Collection;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PterodactylLocations extends AbstractPterodactylApplicationAdapter implements PterodactylLocationsInterface
{
    /**
     * Get a paginated collection of locations.
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
        $response = $this->makeRequest('GET', 'locations', [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to get locations: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        $locations = [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $location) {
                $locations[] = new PterodactylLocation($location);
            }
        }

        return new Collection($locations, $this->getMetaFromResponse($data));
    }

    /**
     * Get all locations.
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
        // Dla metody all, pobieramy wszystkie locations
        $query = array_merge($query, ['per_page' => 100]);
        return $this->paginate($query);
    }

    /**
     * Get a location instance by id.
     *
     * @param int $locationId
     * @param array $query
     * @return PterodactylLocation
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function get(int $locationId, array $query = []): PterodactylLocation
    {
        $response = $this->makeRequest('GET', "locations/$locationId", [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to get location: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        return new PterodactylLocation($data, $this->getMetaFromResponse($data));
    }

    /**
     * Create a new location.
     *
     * @param array $data
     * @return PterodactylLocation
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function create(array $data): PterodactylLocation
    {
        $response = $this->makeRequest('POST', 'locations', [
            'json' => $data
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new Exception(
                sprintf('Failed to create location: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $responseData = $response->toArray();
        
        return new PterodactylLocation($responseData, $this->getMetaFromResponse($data));
    }

    /**
     * Update a specified location.
     *
     * @param int $locationId
     * @param array $data
     * @return PterodactylLocation
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function update(int $locationId, array $data): PterodactylLocation
    {
        $response = $this->makeRequest('PATCH', "locations/$locationId", [
            'json' => $data
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Failed to update location: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $responseData = $response->toArray();
        
        return new PterodactylLocation($responseData, $this->getMetaFromResponse($data));
    }

    /**
     * Delete the given location.
     *
     * @param int $locationId
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function delete(int $locationId): void
    {
        $response = $this->makeRequest('DELETE', "locations/$locationId");

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Failed to delete location: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }
}
