<?php

namespace App\Core\Tests\Integration\Controller;

use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class AuthorizationControllerTest extends BaseTestCase
{
    public function testLoginPageLoads(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorExists('input[id="remember_me"]');
    }

    public function testLoginFormContainsCsrfToken(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testLoginRedirectsToPanelIfAlreadyAuthenticated(): void
    {
        $this->client->loginUser($this->createTestUser());

        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/panel');
    }

    public function testFailedLoginShowsError(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'wrong-email@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testSuccessfulLoginRedirectsToPanel(): void
    {
        $user = $this->createTestUser();

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => $user->getEmail(),
            'password' => 'password',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/panel');
    }

    public function testLogout(): void
    {
        $user = $this->createTestUser();

        $this->client->loginUser($user);

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('/');
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('testuser@example.com');
        $user->setPassword(
            $this->getContainer()->get('security.password_hasher')->hashPassword($user, 'password')
        );
        $user->setRoles(['ROLE_USER']);
        $user->setName('Test');
        $user->setSurname('User');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
