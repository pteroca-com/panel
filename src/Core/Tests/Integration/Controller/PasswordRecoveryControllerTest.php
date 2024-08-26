<?php

namespace App\Core\Tests\Integration\Controller;

use App\Core\Entity\User;
use App\Core\Entity\PasswordResetRequest;
use App\Core\Tests\Integration\BaseTestCase;

class PasswordRecoveryControllerTest extends BaseTestCase
{
    public function testRequestPasswordResetPageLoads(): void
    {
        $crawler = $this->client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="reset_password_request_form"]');
    }

    public function testSubmitPasswordResetRequestWithValidEmail(): void
    {
        $this->createTestUser('testuser@example.com');

        $crawler = $this->client->request('GET', '/reset-password');

        $form = $crawler->selectButton('Send password reset link')->form([
            'reset_password_request_form[email]' => 'testuser@example.com',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'If an account with that email exists, you will receive a password reset email.');
    }

    public function testSubmitPasswordResetRequestWithInvalidEmail(): void
    {
        $crawler = $this->client->request('GET', '/reset-password');

        $form = $crawler->selectButton('Send password reset link')->form([
            'reset_password_request_form[email]' => 'nonexistent@example.com',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'If an account with that email exists, you will receive a password reset email.');
    }

    public function testResetPasswordPageLoadsWithValidToken(): void
    {
        $validToken = $this->createPasswordResetToken('testuser@example.com');

        $crawler = $this->client->request('GET', '/reset-password/' . $validToken);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="reset_password_form"]');
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $invalidToken = 'invalid-token';

        $this->client->request('GET', '/reset-password/' . $invalidToken);

        $this->assertResponseRedirects('/reset-password');
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-danger', 'The password reset token is either invalid or has expired.');
    }

    public function testSuccessfulPasswordReset(): void
    {
        $validToken = $this->createPasswordResetToken('testuser@example.com');

        $crawler = $this->client->request('GET', '/reset-password/' . $validToken);

        $form = $crawler->selectButton('Change password')->form([
            'reset_password_form[newPassword]' => 'NewSecurePassword123!',
            'reset_password_form[confirmPassword]' => 'NewSecurePassword123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Your password has been successfully changed.');
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
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createPasswordResetToken(string $email): string
    {
        $user = $this->createTestUser($email);

        $token = bin2hex(random_bytes(32));

        $resetRequest = new PasswordResetRequest();
        $resetRequest->setUser($user);
        $resetRequest->setToken($token);
        $resetRequest->setExpiresAt(new \DateTime('+1 hour'));
        $resetRequest->setIsUsed(false);

        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        return $token;
    }
}
