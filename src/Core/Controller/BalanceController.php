<?php

namespace App\Core\Controller;

use App\Core\Enum\SettingEnum;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\SettingService;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class BalanceController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/wallet/recharge', name: 'recharge_balance')]
    public function rechargeBalance(
        Request $request,
    ): Response {
        $this->checkPermission();
        $currency = $this->settingService
            ->getSetting(SettingEnum::CURRENCY_NAME->value);
        $form = $this->createFormBuilder()
            ->add('amount', MoneyType::class, [
                'currency' => $currency,
                'label' => sprintf(
                    '%s (%s)',
                    $this->translator->trans('pteroca.recharge.recharge_amount'),
                    $currency
                ),
            ])
            ->add('currency', HiddenType::class, [
                'data' => $currency,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $amount = $data['amount'];
            $currency = $data['currency'];
            try {
                $paymentUrl = $this->paymentService->createPayment(
                    $this->getUser(),
                    $amount,
                    $currency,
                    $this->generateUrl('stripe_success', [], 0) . '?session_id={CHECKOUT_SESSION_ID}',
                    $this->generateUrl('stripe_cancel', [], 0)
                );
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());
                return $this->redirectToRoute('panel', ['routeName' => 'recharge_balance']);
            }

            return $this->redirect($paymentUrl);
        }

        return $this->render('panel/wallet/recharge.html.twig', [
            'form' => $form->createView(),
            'payments' => $this->paymentService->getUserPayments($this->getUser(), 10),
            'balance' => $this->getUser()->getBalance(),
        ]);
    }

    #[Route('/wallet/recharge/success', name: 'stripe_success')]
    public function success(Request $request): Response
    {
        $this->checkPermission();
        $sessionId = $request->query->get('session_id');
        if (empty($sessionId)) {
            $this->addFlash('danger', $this->translator->trans('pteroca.recharge.invalid_session_id'));
            return $this->redirectToRechargeBalance();
        }

        $error = $this->paymentService->finalizePayment($this->getUser(), $sessionId);
        if (!empty($error)) {
            $this->addFlash('danger', $error);
            return $this->redirectToRechargeBalance();
        }

        $this->addFlash('success', $this->translator->trans('pteroca.recharge.payment_success'));
        return $this->redirectToRechargeBalance();
    }

    #[Route('/wallet/recharge/cancel', name: 'stripe_cancel')]
    public function cancel(): Response
    {
        $this->checkPermission();
        $this->addFlash('danger', $this->translator->trans('pteroca.recharge.payment_canceled'));
        return $this->redirectToRechargeBalance();
    }

    private function redirectToRechargeBalance(): Response
    {
        return $this->redirectToRoute('panel', ['routeName' => 'recharge_balance']);
    }
}
