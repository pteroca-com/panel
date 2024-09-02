<?php

namespace App\Core\Tests\Unit\Service\Pterodactyl;

use App\Core\Entity\Product;
use App\Core\Service\Pterodactyl\NodeSelectionService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Exception;
use PHPUnit\Framework\TestCase;
use Timdesm\PterodactylPhpApi\Managers\Node\NodeAllocationManager;
use Timdesm\PterodactylPhpApi\Managers\NodeManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Collection;

class NodeSelectionServiceTest extends TestCase
{
    private PterodactylService $pterodactylService;
    private NodeSelectionService $nodeSelectionService;

    protected function setUp(): void
    {
        $this->pterodactylService = $this->createMock(PterodactylService::class);
        $this->nodeSelectionService = new NodeSelectionService($this->pterodactylService);
    }

    public function testGetBestAllocationIdReturnsCorrectAllocation(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getNodes')->willReturn([1, 2]);
        $product->method('getMemory')->willReturn(1024);
        $product->method('getDiskSpace')->willReturn(10000);

        $nodesMock = $this->createMock(NodeManager::class);
        $nodesMock
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'memory' => 2048,
                    'allocated_resources' => ['memory' => 512, 'disk' => 5000],
                    'disk' => 20000,
                ],
                [
                    'id' => 2,
                    'memory' => 4096,
                    'allocated_resources' => ['memory' => 2048, 'disk' => 10000],
                    'disk' => 30000,
                ]
            );

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock
            ->method('toArray')
            ->willReturn([
                ['id' => 100, 'assigned' => true],
                ['id' => 101, 'assigned' => false],
            ]);

        $nodeAllocationsMock = $this->createMock(NodeAllocationManager::class);
        $nodeAllocationsMock
            ->method('all')
            ->willReturn($collectionMock);

        $apiMock = $this->createMock(PterodactylApi::class);
        $apiMock->nodes = $nodesMock;
        $apiMock->node_allocations = $nodeAllocationsMock;
        $this->pterodactylService->method('getApi')->willReturn($apiMock);

        $allocationId = $this->nodeSelectionService->getBestAllocationId($product);
        $this->assertEquals(101, $allocationId);
    }

    public function testGetBestAllocationIdThrowsExceptionWhenNoSuitableNodeFound(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getNodes')->willReturn([1, 2]);
        $product->method('getMemory')->willReturn(1024);
        $product->method('getDiskSpace')->willReturn(10000);

        $nodesMock = $this->createMock(NodeManager::class);
        $nodesMock
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'memory' => 2048,
                    'allocated_resources' => ['memory' => 1536, 'disk' => 15000],
                    'disk' => 20000,
                ],
                [
                    'id' => 2,
                    'memory' => 4096,
                    'allocated_resources' => ['memory' => 4096, 'disk' => 30000],
                    'disk' => 30000,
                ]
            );

        $apiMock = $this->createMock(PterodactylApi::class);
        $apiMock->nodes = $nodesMock;
        $this->pterodactylService->method('getApi')->willReturn($apiMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No suitable node found with enough resources');

        $this->nodeSelectionService->getBestAllocationId($product);
    }

    public function testGetBestAllocationIdThrowsExceptionWhenNoSuitableAllocationFound(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getNodes')->willReturn([1]);
        $product->method('getMemory')->willReturn(1024);
        $product->method('getDiskSpace')->willReturn(10000);

        $nodesMock = $this->createMock(NodeManager::class);
        $nodesMock
            ->method('get')
            ->willReturn([
                'id' => 1,
                'memory' => 2048,
                'allocated_resources' => ['memory' => 512, 'disk' => 5000],
                'disk' => 20000,
            ]);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock
            ->method('toArray')
            ->willReturn([
                ['id' => 100, 'assigned' => true],
                ['id' => 101, 'assigned' => true],
            ]);

        $nodeAllocationsMock = $this->createMock(NodeAllocationManager::class);
        $nodeAllocationsMock
            ->method('all')
            ->willReturn($collectionMock);

        $apiMock = $this->createMock(PterodactylApi::class);
        $apiMock->nodes = $nodesMock;
        $apiMock->node_allocations = $nodeAllocationsMock;
        $this->pterodactylService->method('getApi')->willReturn($apiMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No suitable allocation found on the best node');

        $this->nodeSelectionService->getBestAllocationId($product);
    }
}
