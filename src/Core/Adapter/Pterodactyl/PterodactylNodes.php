<?php

namespace App\Core\Adapter\Pterodactyl;

use App\Core\Contract\Pterodactyl\PterodactylNodesInterface;
use App\Core\DTO\Pterodactyl\PterodactylNode;

class PterodactylNodes extends AbstractPterodactylAdapter implements PterodactylNodesInterface
{

    public function getAllNodes(array $parameters = []): array
    {
        $options = [];
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        $response = $this->makeRequest('GET', 'nodes', $options);
        $data = $this->validateServerResponse($response, 200);

        $nodes = [];
        foreach ($data['data'] as $node) {
            $nodes[] = new PterodactylNode($node['attributes']);
        }

        return $nodes;
    }

    public function getNode(string $nodeId): PterodactylNode
    {
        $response = $this->makeRequest('GET', "nodes/{$nodeId}");
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylNode($data['data']['attributes']);
    }

    public function updateNode(string $nodeId, array $details): PterodactylNode
    {
        $response = $this->makeRequest('PATCH', "nodes/{$nodeId}", [
            'json' => $details,
        ]);
        $data = $this->validateServerResponse($response, 200);

        return new PterodactylNode($data['data']['attributes']);
    }

    public function createNode(array $details): PterodactylNode
    {
        $response = $this->makeRequest('POST', 'nodes', [
            'json' => $details,
        ]);
        $data = $this->validateServerResponse($response, 201);

        return new PterodactylNode($data['data']['attributes']);
    }

    public function deleteNode(string $nodeId): bool
    {
        $response = $this->makeRequest('DELETE', "nodes/{$nodeId}");
        $this->validateServerResponse($response, 204);

        return true;
    }
}
