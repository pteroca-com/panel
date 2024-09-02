<?php

namespace App\Core\Tests\Integration\Controller;

use App\Core\Controller\UserAccountCrudController;
use App\Core\Entity\User;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Tests\Integration\BaseTestCase;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\User as PterodactylUser;

class UserAccountCrudControllerTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pterodactylApiMock = $this->createMock(PterodactylApi::class);
        $pterodactylApiMock->users = $this->mockPterodactylUserManager();

        $pterodactylServiceMock = $this->createMock(PterodactylService::class);
        $pterodactylServiceMock->method('getApi')->willReturn($pterodactylApiMock);
        $this->getContainer()->set(PterodactylService::class, $pterodactylServiceMock);
    }

    public function testRedirectsToLoginForUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&crudAction=index');

        $this->assertResponseRedirects('/login');
    }

    public function testCannotEditOtherUsersAccount(): void
    {
        $user1 = $this->createTestUser('testuser1@example.com');
        $user2 = $this->createTestUser('testuser2@example.com');

        $this->client->loginUser($user1);

        $this->client->request('GET', '/panel?crudAction=edit&crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&entityId=' . $user2->getId());

        $this->assertResponseRedirects('/panel?crudAction=edit&crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&entityId=' . $user1->getId());
    }

    public function testRedirectsToEditPageForAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&crudAction=index');

        $this->assertResponseRedirects('/panel?crudAction=edit&crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&entityId=' . $user->getId());
    }

    public function testEditPageLoadsForAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/panel?crudAction=edit&crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&entityId=' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertInputValueSame('UserAccount[email]', $user->getEmail());
        $this->assertInputValueSame('UserAccount[name]', $user->getName());
        $this->assertInputValueSame('UserAccount[surname]', $user->getSurname());
        $this->assertSelectorExists('input[name="UserAccount[plainPassword]"]');
    }

    public function testUpdateUserDetails(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/panel?crudAction=edit&crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&entityId=' . $user->getId());

        $form = $crawler->selectButton('Save changes')->form();
        $form['UserAccount[name]'] = 'Updated Name';
        $form['UserAccount[surname]'] = 'Updated Surname';
        $form['UserAccount[plainPassword]'] = 'newpassword123';

        $this->client->submit($form);

        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(UserAccountCrudController::class) . '&entityId=' . $user->getId());

        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());

        $this->assertEquals('Updated Name', $updatedUser->getName());
        $this->assertEquals('Updated Surname', $updatedUser->getSurname());
        $this->assertTrue($this->getContainer()->get('security.password_hasher')->isPasswordValid($updatedUser, 'password'));
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
        $user->setBalance(0);
        $user->setPterodactylUserId(1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function mockPterodactylUserManager()
    {
        $userMock = $this->createMock(PterodactylUser::class);
        $userMock->username = 'pterodactyl_username';

        $userManagerMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'update'])
            ->getMock();

        $userManagerMock->method('get')->willReturn($userMock);
        $userManagerMock->method('update')->willReturn(true);

        return $userManagerMock;
    }
}
