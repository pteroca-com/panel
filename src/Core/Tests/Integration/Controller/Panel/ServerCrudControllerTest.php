<?php

namespace App\Core\Tests\Integration\Controller\Panel;

use App\Core\Controller\Panel\ServerCrudController;
use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class ServerCrudControllerTest extends BaseTestCase
{
    public function testAccessDeniedForNonAdminUser(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(ServerCrudController::class) . '&crudAction=new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessGrantedForAdminUser(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(ServerCrudController::class) . '&crudAction=new');
        $this->assertResponseIsSuccessful();
    }

    private function createTestUser(array $roles = ['ROLE_ADMIN'], string $email = 'admin@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
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
}
