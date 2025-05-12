<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\ProductInterface;
use Exception;

class NodeSelectionService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService
    ) {}

    public function getBestAllocationId(ProductInterface $product): int
    {
        $bestNode = null;
        $bestNodeFreeMemory = 0;
        $bestNodeFreeDisk = 0;

        foreach ($product->getNodes() as $nodeId) {
            $node = $this->pterodactylService->getApi()->nodes->get($nodeId);

            $freeMemory = $node['memory'] - $node['allocated_resources']['memory'];
            $freeDisk = $node['disk'] - $node['allocated_resources']['disk'];

            if ($freeMemory >= $product->getMemory() && $freeDisk >= $product->getDiskSpace()) {
                if ($freeMemory > $bestNodeFreeMemory || ($freeMemory == $bestNodeFreeMemory && $freeDisk > $bestNodeFreeDisk)) {
                    $bestNode = $node;
                    $bestNodeFreeMemory = $freeMemory;
                    $bestNodeFreeDisk = $freeDisk;
                }
            }
        }

        if (!$bestNode) {
            throw new Exception('No suitable node found with enough resources');
        }

        $allocations = $this->pterodactylService->getApi()->node_allocations->all($bestNode['id'])->toArray();
        foreach ($allocations as $allocation) {
            if (!$allocation['assigned']) {
                return $allocation['id'];
            }
        }

        throw new Exception('No suitable allocation found on the best node');
    }
}
