<?php

namespace App\Core\Tests\Integration\Controller;

use App\Core\Entity\Category;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Tests\Integration\BaseTestCase;
use Timdesm\PterodactylPhpApi\Managers\Nest\NestEggManager;
use Timdesm\PterodactylPhpApi\Managers\NodeManager;
use Timdesm\PterodactylPhpApi\Managers\ServerManager;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\Collection;
use Timdesm\PterodactylPhpApi\Resources\Egg;
use Timdesm\PterodactylPhpApi\Resources\Node;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class StoreControllerTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pterodactylApiMock = $this->createMock(PterodactylApi::class);
        $pterodactylApiMock->nest_eggs = $this->mockNestEggManager();
        $pterodactylApiMock->nodes = $this->mockNodeManager();
        $pterodactylApiMock->servers = $this->mockServerManager();

        $pterodactylServiceMock = $this->createMock(PterodactylService::class);
        $pterodactylServiceMock->method('getApi')->willReturn($pterodactylApiMock);
        $this->getContainer()->set(PterodactylService::class, $pterodactylServiceMock);
    }

    public function testStorePageLoadsForAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $category = $this->createTestCategory();

        $crawler = $this->client->request('GET', '/panel?routeName=store');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.category-list');
        $this->assertSelectorTextContains('.card-title', $category->getName());
    }

    public function testStorePageRedirectsForUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/panel?routeName=store');

        $this->assertResponseRedirects('/login');
    }

    public function testCategoryPageLoadsAndDisplaysProducts(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $category = $this->createTestCategory();
        $product = $this->createTestProduct($category);

        $crawler = $this->client->request('GET', '/panel?routeName=store_category&id=' . $category->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.product-list');
        $this->assertSelectorTextContains('.card-title', $product->getName());
    }

    public function testProductPageLoads(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $category = $this->createTestCategory();
        $product = $this->createTestProduct($category);

        $crawler = $this->client->request('GET', '/panel?routeName=store_product&id=' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.product-details');
        $this->assertSelectorTextContains('.product-name', $product->getName());
        $this->assertSelectorTextContains('.list-group-item', $product->getMemory() . ' MB');
    }

    public function testProductNotFound(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?routeName=store_product&id=999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRenewProductPageLoads(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $category = $this->createTestCategory();
        $product = $this->createTestProduct($category);
        $server = $this->createTestServer($user, $product);

        $crawler = $this->client->request('GET', '/panel?routeName=store_server_renew&id=' . $server->getPterodactylServerIdentifier());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.product-details');
        $this->assertSelectorTextContains('.list-group:last-child', $server->getExpiresAt()->format('Y-m-d'));
    }

    private function createTestUser(string $email = 'testuser@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->getContainer()->get('security.password_hasher')->hashPassword($user, 'password')
        );
        $user->setRoles(['ROLE_USER']);
        $user->setName('Test');
        $user->setSurname('User');
        $user->setBalance(1000.00);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestCategory(): Category
    {
        $category = new Category();
        $category->setName('Test Category');
        $category->setDescription('This is a test category.');
        $category->setImagePath('test-category.jpg');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createTestProduct(Category $category): Product
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice(100);
        $product->setCategory($category);
        $product->setIsActive(true);
        $product->setDiskSpace(1024);
        $product->setMemory(2048);
        $product->setCpu(100);
        $product->setBackups(3);
        $product->setDbCount(1);
        $product->setSwap(512);
        $product->setPorts(5);
        $product->setImagePath('test-product.jpg');
        $product->setNest(1);
        $product->setUpdatedAtValue();
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createTestServer(User $user, Product $product): Server
    {
        $server = new Server();
        $server->setUser($user);
        $server->setProduct($product);
        $server->setExpiresAt(new \DateTime('+30 days'));
        $server->setIsSuspended(false);
        $server->setPterodactylServerId(1);
        $server->setPterodactylServerIdentifier('abc1234');
        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $server;
    }

    private function mockNestEggManager(): NestEggManager
    {
        $nestEggMock = $this->createMock(NestEggManager::class);
        $nestEggMock->method('all')->willReturnCallback(function (int $nestId) {
            return new Collection([
                'data' => [
                    new Egg([
                        'id' => 1,
                        'name' => 'Test Egg',
                        'author' => 'Test Author',
                        'description' => 'Test Description',
                        'docker_image' => 'test/egg:latest',
                        'config' => [
                            'startup' => 'java -jar server.jar',
                            'image' => 'test/egg:latest',
                        ],
                    ])
                ]
            ]);
        });

        return $nestEggMock;
    }

    private function mockNodeManager(): NodeManager
    {
        $nodeMock = $this->createMock(NodeManager::class);
        $nodeMock->method('get')->willReturn(new Node([
            'memory' => 4096,
            'disk' => 10240,
            'allocated_resources' => [
                'memory' => 2048,
                'disk' => 5120,
            ],
            'memory_overallocate' => 0,
            'disk_overallocate' => 0,
        ]));

        return $nodeMock;
    }

    private function mockServerManager(): ServerManager
    {
        $serverMock = $this->createMock(ServerManager::class);

        $pterodactylServerData = [
            'attributes' => [
                'limits' => [
                    'memory' => 2048,
                    'disk' => 5120,
                    'io' => 500,
                    'cpu' => 100,
                    'threads' => null,
                    'database_limit' => 1,
                    'allocation_limit' => 1,
                    'backup_limit' => 3,
                ],
                'feature_limits' => [
                    'databases' => 1,
                    'allocations' => 1,
                    'backups' => 3,
                ],
                'relationships' => [
                    'allocations' => [
                        [
                            'ip' => '127.0.0.1',
                            'port' => 25565,
                        ],
                    ],
                ],
            ],
        ];

        $pterodactylServer = new PterodactylServer($pterodactylServerData, $this->createMock(PterodactylApi::class));

        $serverMock->method('get')->willReturn($pterodactylServer);

        return $serverMock;
    }
}
