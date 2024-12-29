<?php

namespace App\Core\Service\Payment;

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
use App\Core\Service\SettingService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentService
{
    public function __construct(
        private readonly PaymentProviderInterface $paymentProvider,
        private readonly PaymentRepository $paymentRepository,
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        private readonly SettingService $settingService,
        private readonly LogService $logService,
        private readonly UserVerificationService $userVerificationService,
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
        if (empty($session)) {
            throw new \Exception($this->translator->trans('pteroca.recharge.failed_to_create_payment'));
        }
        $this->logService->logAction(
            $user,
            LogActionEnum::CREATE_PAYMENT,
            ['amount' => $amount, 'currency' => $currency, 'sessionId' => $session->getId()]
        );
        $this->savePaymentSession($user, $session);
        return $session->getUrl();
    }

    public function finalizePayment(User $user, string $sessionId): ?string
    {
        $session = $this->paymentProvider->retrieveSession($sessionId);
        if (empty($session)) {
            return 'Session not found';
        }

        /** @var Payment|null $payment */
        $payment = $this->paymentRepository->findOneBy(['sessionId' => $sessionId]);
        if (empty($payment)) {;
            return $this->translator->trans('pteroca.recharge.payment_not_found');
        }

        if ($payment->getStatus() === $session->getPaymentStatus()) {
            return $this->translator->trans('pteroca.recharge.payment_already_processed');
        }

        if ($session->getPaymentStatus() === $this->paymentProvider::PAID_STATUS) {
            $amount = $session->getAmountTotal();
            $newBalance = $user->getBalance() + $amount;
            $user->setBalance($newBalance);
            $this->userRepository->save($user);

            $emailMessage = new SendEmailMessage(
                $user->getEmail(),
                $this->translator->trans('pteroca.email.payment.subject'),
                'email/payment_success.html.twig',
                [
                    'amount' => $amount,
                    'currency' => $session->getCurrency(),
                    'internalCurrency' => $this->settingService
                        ->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                    'user' => $user,
                ],
            );
            $this->messageBus->dispatch($emailMessage);

            $this->logService->logAction(
                $user,
                LogActionEnum::BOUGHT_BALANCE,
                ['amount' => $amount, 'currency' => $session->getCurrency(), 'newBalance' => $newBalance]
            );
        }

        $payment->setStatus($session->getPaymentStatus());
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

    private function savePaymentSession(User $user, PaymentSessionDTO $session): void
    {
        $payment = (new Payment())
            ->setAmount($session->getAmountTotal())
            ->setCurrency($session->getCurrency())
            ->setSessionId($session->getId())
            ->setUser($user)
            ->setStatus($session->getPaymentStatus());
        $this->paymentRepository->save($payment);
    }
}
