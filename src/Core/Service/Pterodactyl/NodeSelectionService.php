<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\ProductInterface;
use Exception;

readonly class NodeSelectionService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
    ) {}

    /**
     * @throws Exception
     */
    public function getBestAllocationId(ProductInterface $product): int
    {
        $bestNode = null;
        $bestNodeFreeMemory = 0;
        $bestNodeFreeDisk = 0;

        foreach ($product->getNodes() as $nodeId) {
            $node = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->nodes()
                ->getNode($nodeId);

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

        $allocations = $this->pterodactylApplicationService
            ->getApplicationApi()
            ->nodeAllocations()
            ->all($bestNode['id'])
            ->toArray();

        foreach ($allocations as $allocation) {
            if (!$allocation['assigned']) {
                return $allocation['id'];
            }
        }

        throw new Exception('No suitable allocation found on the best node');
    }
}
