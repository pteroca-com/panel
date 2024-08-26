<?php

namespace App\Core\Tests\Integration\Controller\Panel;

use App\Core\Controller\Panel\PaymentCrudController;
use App\Core\Entity\Payment;
use App\Core\Entity\User;
use App\Core\Tests\Integration\BaseTestCase;

class PaymentCrudControllerTest extends BaseTestCase
{
    public function testAccessDeniedForNonAdminUser(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(PaymentCrudController::class) . '&crudAction=new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessGrantedForAdminUser(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(PaymentCrudController::class) . '&crudAction=new');
        $this->assertResponseIsSuccessful();
    }

    public function testViewPaymentDetails(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $payment = $this->createTestPayment($adminUser);

        $crawler = $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(PaymentCrudController::class) . '&crudAction=detail&entityId=' . $payment->getId());
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', $payment->getSessionId());
        $this->assertSelectorTextContains('body', $payment->getStatus());
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

    private function createTestPayment(User $user): Payment
    {
        $payment = new Payment();
        $payment->setSessionId('test-session-id');
        $payment->setStatus('paid');
        $payment->setAmount(10000);
        $payment->setCurrency('USD');
        $payment->setUser($user);
        $payment->setCreatedAtValue();

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
