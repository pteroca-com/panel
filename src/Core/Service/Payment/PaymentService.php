<?php

namespace App\Core\Service\Payment;

use App\Core\Entity\Payment;
use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\PaymentRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\LogService;
use App\Core\Service\Payment\Provider\PaymentProviderInterface;
use App\Core\Service\SettingService;
use Stripe\Checkout\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentService
{
    public function __construct(
        private PaymentProviderInterface $paymentProvider,
        private PaymentRepository        $paymentRepository,
        private UserRepository           $userRepository,
        private TranslatorInterface      $translator,
        private MessageBusInterface      $messageBus,
        private SettingService           $settingService,
        private LogService               $logService,
        private UserVerificationService  $userVerificationService,
    ) {}

    public function createPayment(
        User $user,
        float $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
    ): string
    {
        $this->userVerificationService->validateUserVerification($user);
        $session = $this->paymentProvider->createSession($amount, $currency, $successUrl, $cancelUrl);
        if (empty($session->url)) {
            throw new \Exception($this->translator->trans('pteroca.recharge.failed_to_create_payment'));
        }
        $this->logService->logAction(
            $user,
            LogActionEnum::CREATE_PAYMENT,
            ['amount' => $amount, 'currency' => $currency, 'sessionId' => $session->id]
        );
        $this->savePaymentSession($user, $session);
        return $session->url;
    }

    public function finalizePayment(User $user, string $sessionId): ?string
    {
        $session = $this->paymentProvider->retrieveSession($sessionId);
        if (empty($session->id)) {
            return 'Session not found';
        }

        /** @var Payment|null $payment */
        $payment = $this->paymentRepository->findOneBy(['sessionId' => $sessionId]);
        if (empty($payment)) {
            return $this->translator->trans('pteroca.recharge.payment_not_found');
        }

        if ($payment->getStatus() === $session->payment_status) {
            return $this->translator->trans('pteroca.recharge.payment_already_processed');
        }

        if ($session->payment_status === $this->paymentProvider::PAID_STATUS) {
            $amount = $session->amount_total;
            $newBalance = $user->getBalance() + $amount;
            $user->setBalance($newBalance);
            $this->userRepository->save($user);

            $emailMessage = new SendEmailMessage(
                $user->getEmail(),
                $this->translator->trans('pteroca.email.payment.subject'),
                'email/payment_success.html.twig',
                [
                    'amount' => $amount,
                    'currency' => $session->currency,
                    'internalCurrency' => $this->settingService
                        ->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                    'user' => $user,
                ],
            );
            $this->messageBus->dispatch($emailMessage);

            $this->logService->logAction(
                $user,
                LogActionEnum::BOUGHT_BALANCE,
                ['amount' => $amount, 'currency' => $session->currency, 'newBalance' => $newBalance]
            );
        }

        $payment->setStatus($session->payment_status);
        $this->paymentRepository->save($payment);
        return null;
    }

    public function getUserPayments(User $user, ?int $limit = null): array
    {
        return $this->paymentRepository->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function savePaymentSession(User $user, Session $session): void
    {
        $payment = (new Payment())
            ->setAmount($session->amount_total)
            ->setCurrency($session->currency)
            ->setSessionId($session->id)
            ->setUser($user)
            ->setStatus($session->payment_status);
        $this->paymentRepository->save($payment);
    }
}
