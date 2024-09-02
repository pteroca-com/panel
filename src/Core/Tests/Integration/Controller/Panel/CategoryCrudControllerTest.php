<?php

namespace App\Core\Tests\Integration\Controller\Panel;

use App\Core\Controller\Panel\CategoryCrudController;
use App\Core\Entity\Category;
use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class CategoryCrudControllerTest extends BaseTestCase
{
    public function testAccessDeniedForNonAdminUser(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(CategoryCrudController::class) . '&crudAction=new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessGrantedForAdminUser(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(CategoryCrudController::class) . '&crudAction=new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateCategory(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $crawler = $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(CategoryCrudController::class) . '&crudAction=new');
        $form = $crawler->selectButton('Add category')->form([
            'Category[name]' => 'Test Category',
            'Category[description]' => 'This is a test category',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(CategoryCrudController::class));

        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'Test Category']);
        $this->assertNotNull($category);
        $this->assertEquals('Test Category', $category->getName());
        $this->assertEquals('This is a test category', $category->getDescription());
    }

    public function testEditCategory(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $category = $this->createTestCategory();

        $crawler = $this->client->request('GET', '/panel?crudAction=edit&crudControllerFqcn=' . urlencode(CategoryCrudController::class) . '&entityId=' . $category->getId());
        $form = $crawler->selectButton('Save category')->form([
            'Category[name]' => 'Updated Category Name',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(CategoryCrudController::class) . '&entityId=' . $category->getId());

        $updatedCategory = $this->entityManager->getRepository(Category::class)->find($category->getId());
        $this->assertEquals('Updated Category Name', $updatedCategory->getName());
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
        $category->setImagePath('test-image.jpg');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
