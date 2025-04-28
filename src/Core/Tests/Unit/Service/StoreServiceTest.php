<?php

namespace App\Core\Tests\Unit\Service;

use App\Core\Entity\Category;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Repository\CategoryRepository;
use App\Core\Repository\ProductRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\StoreService;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\Managers\Nest\NestEggManager;
use Timdesm\PterodactylPhpApi\Managers\NodeManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Collection;
use Timdesm\PterodactylPhpApi\Resources\Node;

class StoreServiceTest extends TestCase
{
    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;
    private PterodactylService $pterodactylService;
    private TranslatorInterface $translator;
    private StoreService $storeService;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->pterodactylService = $this->createMock(PterodactylService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->storeService = new StoreService(
            $this->categoryRepository,
            $this->productRepository,
            $this->pterodactylService,
            $this->translator,
            '/images/categories',
            '/images/products'
        );
    }

    public function testGetCategories(): void
    {
        $category = new Category();
        $category->setImagePath('category1.jpg');

        $this->categoryRepository
            ->method('findAll')
            ->willReturn([$category]);

        $categories = $this->storeService->getCategories();

        $this->assertCount(1, $categories);
        $this->assertEquals('/images/categories/category1.jpg', $categories[0]->getImagePath());
    }

    public function testGetCategory(): void
    {
        $category = new Category();

        $this->categoryRepository
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $result = $this->storeService->getCategory(1);

        $this->assertSame($category, $result);
    }

    public function testGetCategoryProducts(): void
    {
        $category = new Category();
        $product = new Product();
        $product->setImagePath('product1.jpg');

        $this->productRepository
            ->method('findBy')
            ->with([
                'category' => $category,
                'isActive' => true,
            ])
            ->willReturn([$product]);

        $products = $this->storeService->getCategoryProducts($category);

        $this->assertCount(1, $products);
        $this->assertEquals('/images/products/product1.jpg', $products[0]->getImagePath());
    }

    public function testGetActiveProductWithActiveProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getIsActive')->willReturn(true);

        $this->productRepository
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $result = $this->storeService->getActiveProduct(1);

        $this->assertSame($product, $result);
    }

    public function testGetActiveProductWithInactiveProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getIsActive')->willReturn(false);

        $this->productRepository
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $result = $this->storeService->getActiveProduct(1);

        $this->assertNull($result);
    }

    public function testGetActiveProductWithNonExistentProduct(): void
    {
        $this->productRepository
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $result = $this->storeService->getActiveProduct(1);

        $this->assertNull($result);
    }

    public function testPrepareProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->exactly(2))
            ->method('getImagePath')
            ->willReturn('product1.jpg');

        $product->expects($this->once())
            ->method('setImagePath')
            ->with('/images/products/product1.jpg');

        $result = $this->storeService->prepareProduct($product);

        $this->assertSame($product, $result);
    }

    public function testGetProductEggs(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getNest')->willReturn(1);
        $product->method('getEggs')->willReturn([1]);

        $api = $this->createMock(PterodactylApi::class);
        $api->nest_eggs = $this->createMock(NestEggManager::class);
        $collection = $this->createMock(Collection::class);
        $collection->method('toArray')->willReturn([
            (object) ['id' => 1],
            (object) ['id' => 2]
        ]);
        $api->nest_eggs
            ->method('all')
            ->with(1)
            ->willReturn($collection);

        $this->pterodactylService
            ->method('getApi')
            ->willReturn($api);

        $eggs = $this->storeService->getProductEggs($product);

        $this->assertCount(1, $eggs);
        $this->assertEquals(1, $eggs[0]->id);
    }

    public function testProductHasNodeWithResourcesWithValidNode(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getNodes')->willReturn([1]);
        $product->method('getMemory')->willReturn(512);
        $product->method('getDiskSpace')->willReturn(1024);

        $api = $this->createMock(PterodactylApi::class);
        $api->nodes = $this->createMock(NodeManager::class);

        $node = $this->createMock(Node::class);
        $node->method('toArray')->willReturn([
            'memory' => 2048,
            'memory_overallocate' => 0,
            'disk' => 4096,
            'disk_overallocate' => 0,
            'allocated_resources' => ['memory' => 512, 'disk' => 1024]
        ]);

        $api->nodes
            ->method('get')
            ->with(1)
            ->willReturn($node);

        $this->pterodactylService
            ->method('getApi')
            ->willReturn($api);

        $result = $this->storeService->productHasNodeWithResources($product);

        $this->assertTrue($result);
    }

    public function testProductHasNodeWithResourcesWithInvalidNode(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getNodes')->willReturn([1]);
        $product->method('getMemory')->willReturn(512);
        $product->method('getDiskSpace')->willReturn(1024);

        $api = $this->createMock(PterodactylApi::class);
        $api->nodes = $this->createMock(NodeManager::class);
        $node = $this->createMock(Node::class);
        $node->method('toArray')->willReturn([
            'memory' => 1024,
            'memory_overallocate' => 0,
            'disk' => 2048,
            'disk_overallocate' => 0,
            'allocated_resources' => ['memory' => 1024, 'disk' => 2048]
        ]);
        $api->nodes
            ->method('get')
            ->with(1)
            ->willReturn($node);

        $this->pterodactylService
            ->method('getApi')
            ->willReturn($api);

        $result = $this->storeService->productHasNodeWithResources($product);
        $this->assertFalse($result);
    }

    public function testValidateBoughtProductThrowsExceptionForInvalidEgg(): void
    {
        $user = $this->createMock(User::class);
        $product = $this->createMock(Product::class);
        $product->method('getEggs')->willReturn([1]);

        $this->translator
            ->method('trans')
            ->with('pteroca.store.egg_not_found')
            ->willReturn('Egg not found');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Egg not found');

        $this->storeService->validateBoughtProduct($product, 2);
    }

    public function testValidateBoughtProductThrowsExceptionForInsufficientFunds(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBalance')->willReturn(100.00);

        $product = $this->createMock(Product::class);
        $product->method('getPrice')->willReturn(20000.00);

        $server = $this->createMock(Server::class);

        $this->translator
            ->method('trans')
            ->with('pteroca.store.not_enough_funds')
            ->willReturn('Not enough funds');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not enough funds');

        $this->storeService->validateBoughtProduct($product, 1, $server);
    }

    public function testValidateBoughtProductSucceeds(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBalance')->willReturn(20000.00);

        $product = $this->createMock(Product::class);
        $product->method('getPrice')->willReturn(20000.00);
        $product->method('getEggs')->willReturn([1]);

        $this->storeService->validateBoughtProduct($product, 1);

        $this->assertTrue(true);
    }
}
