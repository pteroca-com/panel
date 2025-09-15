<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Contract\Pterodactyl\Application\PterodactylNodesInterface;
use App\Core\DTO\Pterodactyl\Application\PterodactylNode;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylNodes extends AbstractPterodactylApplicationAdapter implements PterodactylNodesInterface
{

    public function getAllNodes(array $parameters = []): Collection
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', 'nodes', $options);
        $data = $this->validateServerResponse($response, 200);

        $nodes = [];
        foreach ($this->getDataFromResponse($data) as $node) {
            $nodes[] = new PterodactylNode($node['attributes']);
        }

        return new Collection($nodes, $this->getMetaFromResponse($data));
    }

    public function paginateNodes(int $page = 1, array $parameters = []): Collection
    {
        $options = [
            'query' => array_merge(['page' => $page], $parameters)
        ];

        $response = $this->makeRequest('GET', 'nodes', $options);
        $data = $this->validateServerResponse($response, 200);

        $nodes = [];
        foreach ($this->getDataFromResponse($data) as $node) {
            $nodes[] = new PterodactylNode($node['attributes']);
        }

        return new Collection($nodes, $this->getMetaFromResponse($data));
    }

    public function getNode(string $nodeId): PterodactylNode
    {
        $response = $this->makeRequest('GET', "nodes/{$nodeId}");
        $data = $this->validateServerResponse($response, 200);


        return new PterodactylNode($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function getNodeConfiguration(string $nodeId): array
    {
        $response = $this->makeRequest('GET', "nodes/{$nodeId}/configuration");
        $data = $this->validateServerResponse($response, 200);

        return $this->getDataFromResponse($data);
    }

    public function updateNode(string $nodeId, array $details): PterodactylNode
    {
        $response = $this->makeRequest('PATCH', "nodes/{$nodeId}", [
            'json' => $details,
        ]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylNode($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function createNode(array $details): PterodactylNode
    {
        $response = $this->makeRequest('POST', 'nodes', [
            'json' => $details,
        ]);
        $data = $this->validateServerResponse($response, 201);

        return new PterodactylNode($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function deleteNode(string $nodeId): bool
    {
        $response = $this->makeRequest('DELETE', "nodes/{$nodeId}");
        $this->validateServerResponse($response, 204);

        return true;
    }

    public function getAllocations(string $nodeId, array $parameters = []): Collection
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', "nodes/{$nodeId}/allocations", $options);
        $data = $this->validateServerResponse($response, 200);

        return new Collection($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function createAllocations(string $nodeId, array $data): Collection
    {
        $response = $this->makeRequest('POST', "nodes/{$nodeId}/allocations", [
            'json' => $data,
        ]);
        $responseData = $this->validateServerResponse($response, 201);

        return new Collection($this->getDataFromResponse($responseData), $this->getMetaFromResponse($responseData));
    }

    public function deleteAllocation(string $nodeId, string $allocationId): bool
    {
        $response = $this->makeRequest('DELETE', "nodes/{$nodeId}/allocations/{$allocationId}");
        $this->validateServerResponse($response, 204);

        return true;
    }
}
