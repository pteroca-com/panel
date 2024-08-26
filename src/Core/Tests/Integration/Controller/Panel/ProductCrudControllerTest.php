<?php

namespace App\Core\Tests\Integration\Controller\Panel;

use App\Core\Controller\Panel\ProductCrudController;
use App\Core\Entity\Product;
use App\Core\Entity\Category;
use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class ProductCrudControllerTest extends BaseTestCase
{
    public function testAccessDeniedForNonAdminUser(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(ProductCrudController::class) . '&crudAction=new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessGrantedForAdminUser(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(ProductCrudController::class) . '&crudAction=new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateProduct(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $category = $this->createTestCategory();

        $crawler = $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(ProductCrudController::class) . '&crudAction=new');
        $form = $crawler->selectButton('Add product')->form([
            'Product[name]' => 'Test Product',
            'Product[description]' => 'This is a test product',
            'Product[price]' => 100.00,
            'Product[isActive]' => true,
            'Product[category]' => $category->getId(),
            'Product[diskSpace]' => 1024,
            'Product[memory]' => 2048,
            'Product[io]' => 500,
            'Product[cpu]' => 100,
            'Product[dbCount]' => 2,
            'Product[swap]' => 512,
            'Product[backups]' => 3,
            'Product[ports]' => 5,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(ProductCrudController::class));

        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => 'Test Product']);
        $this->assertNotNull($product);
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('This is a test product', $product->getDescription());
        $this->assertEquals(100.00, $product->getPrice());
        $this->assertTrue($product->getIsActive());
        $this->assertEquals(1024, $product->getDiskSpace());
        $this->assertEquals(2048, $product->getMemory());
    }

    public function testEditProduct(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $product = $this->createTestProduct();

        $crawler = $this->client->request('GET', '/panel?crudAction=edit&crudControllerFqcn=' . urlencode(ProductCrudController::class) . '&entityId=' . $product->getId());
        $form = $crawler->selectButton('Save product')->form([
            'Product[name]' => 'Updated Product Name',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(ProductCrudController::class) . '&entityId=' . $product->getId());

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('Updated Product Name', $updatedProduct->getName());
    }

    private function createTestUser(array $roles = ['ROLE_ADMIN']): User
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword(
            $this->getContainer()->get('security.password_hasher')->hashPassword($user, 'password')
        );
        $user->setRoles($roles);
        $user->setName('Admin');
        $user->setSurname('User');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestCategory(): Category
    {
        $category = new Category();
        $category->setName('Test Category');
        $category->setDescription('This is a test category.');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createTestProduct(): Product
    {
        $category = $this->createTestCategory();

        $product = new Product();
        $product->setName('Test Product');
        $product->setDescription('This is a test product.');
        $product->setPrice(100.00);
        $product->setIsActive(true);
        $product->setDiskSpace(1024);
        $product->setMemory(2048);
        $product->setIo(500);
        $product->setCpu(100);
        $product->setDbCount(2);
        $product->setSwap(512);
        $product->setBackups(3);
        $product->setPorts(5);
        $product->setCategory($category);
        $product->setUpdatedAtValue();
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
