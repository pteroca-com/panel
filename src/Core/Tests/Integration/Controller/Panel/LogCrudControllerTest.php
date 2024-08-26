<?php

namespace App\Core\Tests\Integration\Controller\Panel;

use App\Core\Controller\Panel\LogCrudController;
use App\Core\Entity\Log;
use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class LogCrudControllerTest extends BaseTestCase
{
    public function testAccessDeniedForNonAdminUser(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(LogCrudController::class) . '&crudAction=new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessGrantedForAdminUser(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(LogCrudController::class) . '&crudAction=new');
        $this->assertResponseIsSuccessful();
    }

    public function testViewLogDetails(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $log = $this->createTestLog($adminUser);

        $crawler = $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(LogCrudController::class) . '&crudAction=detail&entityId=' . $log->getId());
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', $log->getId());
        $this->assertSelectorTextContains('body', $log->getIpAddress());
        $this->assertSelectorTextContains('body', $adminUser->getEmail());
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

    private function createTestLog(User $user): Log
    {
        $log = new Log();
        $log->setActionId('TEST_ACTION');
        $log->setDetails(json_encode(['key' => 'value']));
        $log->setIpAddress('127.0.0.1');
        $log->setCreatedAtValue();
        $log->setUser($user);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }
}
