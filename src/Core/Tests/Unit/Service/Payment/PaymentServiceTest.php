<?php

namespace App\Core\Tests\Unit\Service\Payment;

use App\Core\DTO\PaymentSessionDTO;
use App\Core\Entity\Payment;
use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Provider\Payment\PaymentProviderInterface;
use App\Core\Repository\PaymentRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\Logs\LogService;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\SettingService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentServiceTest extends TestCase
{
    private PaymentProviderInterface $paymentProvider;
    private PaymentRepository $paymentRepository;
    private UserRepository $userRepository;
    private TranslatorInterface $translator;
    private MessageBusInterface $messageBus;
    private SettingService $settingService;
    private LogService $logService;
    private UserVerificationService $userVerificationService;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        $this->paymentProvider = $this->createMock(PaymentProviderInterface::class);
        $this->paymentRepository = $this->createMock(PaymentRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->logService = $this->createMock(LogService::class);
        $this->userVerificationService = $this->createMock(UserVerificationService::class);

        $this->createPaymentService();
    }

    private function createPaymentService(): void
    {
        $this->paymentService = new PaymentService(
            $this->paymentProvider,
            $this->paymentRepository,
            $this->userRepository,
            $this->translator,
            $this->messageBus,
            $this->settingService,
            $this->logService,
            $this->userVerificationService
        );
    }

    public function testCreatePayment(): void
    {
        $user = $this->prepareMockUser();
        $paymentUrl = 'https://example.com/';

        $this->userVerificationService
            ->expects($this->once())
            ->method('validateUserVerification')
            ->with($user);

        $session = $this->createPaymentSessionDTO('session_id', 15000, 'USD', 'created', $paymentUrl);

        $this->paymentProvider
            ->method('createSession')
            ->willReturn($session);

        $this->logService
            ->expects($this->once())
            ->method('logAction')
            ->with($user, LogActionEnum::CREATE_PAYMENT, ['amount' => 100, 'currency' => 'USD', 'sessionId' => 'session_id']);

        $result = $this->paymentService->createPayment($user, 100, 'USD', 'https://example.com/success', 'https://example.com/cancel');

        $this->assertEquals($paymentUrl, $result);
    }

    public function testCreatePaymentThrowsExceptionWhenSessionIsEmpty(): void
    {
        $user = $this->prepareMockUser();

        $this->userVerificationService
            ->expects($this->once())
            ->method('validateUserVerification')
            ->with($user);

        $this->paymentProvider
            ->method('createSession')
            ->willReturn(null);

        $this->translator
            ->method('trans')
            ->with('pteroca.recharge.failed_to_create_payment')
            ->willReturn('Failed to create payment.');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create payment.');

        $this->paymentService->createPayment($user, 100, 'USD', 'https://example.com/success', 'https://example.com/cancel');
    }

    public function testFinalizePayment(): void
    {
        $initialBalance = 50;
        $additionalBalance = 100;
        $summaryBalance = $initialBalance + $additionalBalance;

        $user = $this->prepareMockUser($initialBalance);
        $userEmail = 'test@test.com';
        $session = $this->createPaymentSessionDTO('session_id', $additionalBalance * 100, 'USD', 'paid', 'https://example.com/');

        $this->paymentProvider
            ->method('retrieveSession')
            ->with($session->getId())
            ->willReturn($session);

        $payment = $this->createMock(Payment::class);
        $payment->method('getStatus')->willReturn('unpaid');

        $this->paymentRepository
            ->method('findOneBy')
            ->with(['sessionId' => $session->getId()])
            ->willReturn($payment);

        $user->expects($this->once())->method('setBalance')->with($summaryBalance);
        $user->expects($this->once())->method('getEmail')->willReturn($userEmail);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->settingService
            ->method('getSetting')
            ->with(SettingEnum::INTERNAL_CURRENCY_NAME->value)
            ->willReturn('Credits');

        $emailMessage = new SendEmailMessage(
            $userEmail,
            $this->translator->trans('pteroca.email.payment.subject'),
            'email/payment_success.html.twig',
            [
                'amount' => $summaryBalance,
                'currency' => $session->getCurrency(),
                'internalCurrency' => 'Credits',
                'user' => $user,
            ]
        );
        $envelope = new Envelope($emailMessage, [new HandledStamp('result', 'handler_name')]);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn($envelope);

        $this->logService
            ->expects($this->once())
            ->method('logAction')
            ->with($user, LogActionEnum::BOUGHT_BALANCE, ['amount' => $additionalBalance, 'currency' => 'USD', 'newBalance' => $summaryBalance]);

        $this->paymentRepository
            ->expects($this->once())
            ->method('save')
            ->with($payment);

        $result = $this->paymentService->finalizePayment($user, $session->getId());

        $this->assertNull($result);
    }

    public function testFinalizePaymentSessionNotFound(): void
    {
        $user = $this->prepareMockUser();

        $this->paymentProvider
            ->method('retrieveSession')
            ->with('session_id')
            ->willReturn(null);

        $result = $this->paymentService->finalizePayment($user, 'session_id');

        $this->assertEquals('Session not found', $result);
    }

    public function testGetUserPayments(): void
    {
        $user = $this->prepareMockUser();
        $payments = [new Payment(), new Payment()];

        $abstractQuery = $this->createMock(Query::class);
        $abstractQuery->method('getResult')->willReturn($payments);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($abstractQuery);

        $this->paymentRepository
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($queryBuilder);

        $result = $this->paymentService->getUserPayments($user, 2);

        $this->assertCount(2, $result);
    }

    private function prepareMockUser(float $initialBalance = 0): User|MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getBalance')->willReturn($initialBalance);
        return $user;
    }

    private function createPaymentSessionDTO(string $id, int $amount, string $currency, string $status, string $url): PaymentSessionDTO
    {
        return new PaymentSessionDTO($id, $amount, $currency, $status, $url);
    }
}
