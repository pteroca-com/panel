<?php

namespace App\Core\Tests\Integration\Controller;

use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class RegistrationControllerTest extends BaseTestCase
{
    public function testRegistrationPageLoads(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="registration_form"]');
    }

    public function testSuccessfulRegistration(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('register-submit')->form([
            'registration_form[name]' => 'John',
            'registration_form[surname]' => 'Doe',
            'registration_form[email]' => 'john.doe@example.com',
            'registration_form[plainPassword]' => 'SecurePass123!',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/panel');

        $this->client->followRedirect();
    }

    public function testRegistrationFormValidation(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('register-submit')->form();
        $this->client->submit($form);

        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'Email address is required.');
        $this->assertSelectorTextContains('.alert-danger', 'Please enter your first name.');
        $this->assertSelectorTextContains('.alert-danger', 'Please enter your last name.');
        $this->assertSelectorTextContains('.alert-danger', 'Please enter a password.');
        $this->assertSelectorTextContains('.alert-danger', 'You must accept the terms of service to continue.');
    }

    public function testUserIsSavedInDatabaseAfterRegistration(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('register-submit')->form([
            'registration_form[name]' => 'Jane',
            'registration_form[surname]' => 'Doe',
            'registration_form[email]' => 'jane.doe@example.com',
            'registration_form[plainPassword]' => 'SecurePass123!',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/panel');

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'jane.doe@example.com']);

        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());
    }

    public function testInvalidEmailFormat(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('register-submit')->form([
            'registration_form[email]' => 'invalid-email',
        ]);

        $this->client->submit($form);

        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'Email address is invalid.');
    }

    public function testFormRetainsDataAfterValidationError(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('register-submit')->form([
            'registration_form[name]' => 'John',
            'registration_form[email]' => 'john.doe@example.com',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->client->submit($form);

        $this->assertInputValueSame('registration_form[name]', 'John');
        $this->assertInputValueSame('registration_form[email]', 'john.doe@example.com');
    }

    public function testRegistrationWithExistingEmail(): void
    {
        $this->testSuccessfulRegistration();

        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('register-submit')->form([
            'registration_form[name]' => 'John',
            'registration_form[surname]' => 'Doe',
            'registration_form[email]' => 'john.doe@example.com',
            'registration_form[plainPassword]' => 'SecurePass123!',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->client->submit($form);

        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'An account with that email address already exists.');
    }
}
