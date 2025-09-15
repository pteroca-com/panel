<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylNodeAllocationsInterface;
use App\Core\DTO\Pterodactyl\Application\PterodactylNodeAllocation;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylNodeAllocations extends AbstractPterodactylApplicationAdapter implements PterodactylNodeAllocationsInterface
{
    /**
     * Get a paginated collection of allocations for a specific node.
     *
     * @param int $nodeId
     * @param array $query
     * @return Collection
     */
    public function paginate(int $nodeId, array $query = []): Collection
    {
        $response = $this->makeRequest('GET', "nodes/{$nodeId}/allocations", [
            'query' => $query
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception(
                sprintf('Failed to get node allocations: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();
        
        $allocations = [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $allocation) {
                $allocations[] = new PterodactylNodeAllocation($allocation);
            }
        }

        return new Collection($allocations, $this->getMetaFromResponse($data));
    }

    /**
     * Get all allocations for a specific node.
     *
     * @param int $nodeId
     * @param array $query
     * @return Collection
     */
    public function all(int $nodeId, array $query = []): Collection
    {
        // Dla metody all, pobieramy pierwszą stronę bez limitów
        $query = array_merge($query, ['per_page' => 100]);
        return $this->paginate($nodeId, $query);
    }

    /**
     * Create new allocations for a node.
     *
     * @param int $nodeId
     * @param array $data
     * @return Collection
     */
    public function create(int $nodeId, array $data): Collection
    {
        $response = $this->makeRequest('POST', "nodes/{$nodeId}/allocations", [
            'json' => $data
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception(
                sprintf('Failed to create node allocations: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $responseData = $response->toArray();
        
        $allocations = [];
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            foreach ($responseData['data'] as $allocation) {
                $allocations[] = new PterodactylNodeAllocation($allocation);
            }
        } elseif (isset($responseData['attributes'])) {
            // Jeśli zwrócona jest pojedyncza alokacja
            $allocations[] = new PterodactylNodeAllocation($responseData);
        }

        return new Collection($allocations, $this->getMetaFromResponse($responseData));
    }

    /**
     * Delete a specific allocation from a node.
     *
     * @param int $nodeId
     * @param int $allocationId
     * @return void
     */
    public function delete(int $nodeId, int $allocationId): void
    {
        $response = $this->makeRequest('DELETE', "nodes/{$nodeId}/allocations/{$allocationId}");

        if ($response->getStatusCode() !== 204) {
            throw new \Exception(
                sprintf('Failed to delete node allocation: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }
}
