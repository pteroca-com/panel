<?php

namespace App\Core\Tests\Integration\Controller;

use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class BalanceControllerTest extends BaseTestCase
{
    public function testRechargeBalancePageLoadsForAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/panel?routeName=recharge_balance');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorTextContains('label', 'Recharge amount (USD)');
    }

    public function testRechargeBalancePageRedirectsForUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/panel?routeName=recharge_balance');

        $this->assertResponseRedirects('/login');
    }

    public function testRechargeBalanceFormSubmissionSuccess(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/panel?routeName=recharge_balance');

        $form = $crawler->selectButton('Recharge USD')->form([
            'form[amount]' => 100.00,
        ]);

        $this->client->submit($form);

        $this->client->followRedirects();

        $this->assertResponseRedirects('/panel?routeName=recharge_balance');
    }

    public function testRechargeBalanceFormSubmissionFailure(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/panel?routeName=recharge_balance');

        $form = $crawler->selectButton('Recharge USD')->form([
            'form[amount]' => 0.00,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?routeName=recharge_balance');
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
}
