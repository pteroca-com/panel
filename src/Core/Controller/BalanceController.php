<?php

namespace App\Core\Controller;

use App\Core\Enum\SettingEnum;
use App\Core\Event\Balance\BalanceRechargePageAccessedEvent;
use App\Core\Event\Balance\BalanceRechargeFormDataLoadedEvent;
use App\Core\Event\Balance\BalancePaymentCallbackAccessedEvent;
use App\Core\Event\Form\FormBuildEvent;
use App\Core\Event\View\ViewDataEvent;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\SettingService;
use App\Core\Trait\EventContextTrait;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class BalanceController extends AbstractController
{
    use EventContextTrait;

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

        // Buduj context dla eventów
        $context = $this->buildMinimalEventContext($request);

        // 1. Emit BalanceRechargePageAccessedEvent
        $this->dispatchEvent(new BalanceRechargePageAccessedEvent(
            $this->getUser()->getId(),
            $this->getUser()->getBalance(),
            $context
        ));

        $currency = $this->settingService
            ->getSetting(SettingEnum::CURRENCY_NAME->value);

        $formBuilder = $this->createFormBuilder()
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
            ]);
        
        // Emit FormBuildEvent dla pluginów
        $this->dispatchEvent(new FormBuildEvent($formBuilder, 'balance_recharge', $context));
        
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        
        // 2. Emit BalanceRechargeFormDataLoadedEvent
        $this->dispatchEvent(new BalanceRechargeFormDataLoadedEvent(
            $this->getUser()->getId(),
            $this->getUser()->getBalance(),
            $currency,
            $context
        ));
        
        // 3. Przygotuj dane widoku
        $viewData = [
            'form' => $form->createView(),
            'balance' => $this->getUser()->getBalance(),
        ];
        
        // 4. Emit ViewDataEvent
        $viewEvent = $this->dispatchEvent(new ViewDataEvent(
            'balance_recharge',
            $viewData,
            $this->getUser(),
            $context
        ));

        return $this->render('panel/wallet/recharge.html.twig', $viewEvent->getViewData());
    }

    #[Route('/wallet/recharge/success', name: 'stripe_success')]
    public function success(Request $request): Response
    {
        $this->checkPermission();

        $sessionId = $request->query->get('session_id');

        // Buduj context dla eventów
        $context = $this->buildMinimalEventContext($request);

        // 1. Emit BalancePaymentCallbackAccessedEvent
        $this->dispatchEvent(new BalancePaymentCallbackAccessedEvent(
            $this->getUser()->getId(),
            $sessionId,
            'success',
            $context
        ));
        
        if (empty($sessionId)) {
            $this->addFlash('danger', $this->translator->trans('pteroca.recharge.invalid_session_id'));
            return $this->redirectToRechargeBalance();
        }

        // PaymentService emituje eventy domenowe wewnętrznie
        $error = $this->paymentService->finalizePayment($this->getUser(), $sessionId);
        if (!empty($error)) {
            $this->addFlash('danger', $error);
            return $this->redirectToRechargeBalance();
        }

        $this->addFlash('success', $this->translator->trans('pteroca.recharge.payment_success'));

        return $this->redirectToRechargeBalance();
    }

    #[Route('/wallet/recharge/cancel', name: 'stripe_cancel')]
    public function cancel(Request $request): Response
    {
        $this->checkPermission();

        // Buduj context dla eventów
        $context = $this->buildMinimalEventContext($request);

        // Emit BalancePaymentCallbackAccessedEvent
        $this->dispatchEvent(new BalancePaymentCallbackAccessedEvent(
            $this->getUser()->getId(),
            null,
            'cancel',
            $context
        ));
        
        $this->addFlash('danger', $this->translator->trans('pteroca.recharge.payment_canceled'));

        return $this->redirectToRechargeBalance();
    }

    private function redirectToRechargeBalance(): Response
    {
        return $this->redirectToRoute('panel', ['routeName' => 'recharge_balance']);
    }
}
