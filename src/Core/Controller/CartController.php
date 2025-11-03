<?php

namespace App\Core\Controller;

use App\Core\Attribute\RequiresVerifiedEmail;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Cart\CartTopUpPageAccessedEvent;
use App\Core\Event\Cart\CartTopUpDataLoadedEvent;
use App\Core\Event\Cart\CartPaymentRedirectEvent;
use App\Core\Event\Cart\CartConfigurePageAccessedEvent;
use App\Core\Event\Cart\CartConfigureDataLoadedEvent;
use App\Core\Event\Cart\CartBuyRequestedEvent;
use App\Core\Event\Cart\CartRenewPageAccessedEvent;
use App\Core\Event\Cart\CartRenewDataLoadedEvent;
use App\Core\Event\Cart\CartRenewBuyRequestedEvent;
use App\Core\Event\Payment\PaymentGatewaysCollectedEvent;
use App\Core\Form\Cart\TopUpBalanceType;
use App\Core\Form\Cart\ServerOrderType;
use App\Core\Form\Cart\ServerRenewType;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\Payment\PaymentGatewayManager;
use App\Core\Service\Server\CreateServerService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\Server\ServerSlotPricingService;
use App\Core\Service\SettingService;
use App\Core\Service\StoreService;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartController extends AbstractController
{

    public function __construct(
        private readonly StoreService $storeService,
        private readonly ServerRepository $serverRepository,
        private readonly ServerSubuserRepository $serverSubuserRepository,
        private readonly TranslatorInterface $translator,
        private readonly ServerSlotPricingService $serverSlotPricingService,
    ) {}

    #[Route('/cart/topup', name: 'cart_topup', methods: ['GET', 'POST'])]
    #[RequiresVerifiedEmail]
    public function topUpBalance(
        Request $request,
        SettingService $settingService,
        PaymentService $paymentService,
        PaymentGatewayManager $gatewayManager,
    ): Response
    {
        $currency = $settingService->getSetting(SettingEnum::CURRENCY_NAME->value);

        // Dispatch event to allow plugins to register payment gateways
        $context = $this->buildMinimalEventContext($request);
        $gatewaysEvent = new PaymentGatewaysCollectedEvent($gatewayManager, $context);
        $this->dispatchEvent($gatewaysEvent);

        // Get available payment gateways for the currency
        $availableGateways = $gatewayManager->getProvidersForCurrency($currency);

        // Prepare choices for gateway field
        $gatewayChoices = [];
        foreach ($availableGateways as $gateway) {
            $gatewayChoices[$gateway->displayName] = $gateway->identifier;
        }

        // Prepare initial data from query params if present (for GET requests from recharge_balance)
        $initialData = [];
        if ($request->query->has('amount')) {
            $initialData['amount'] = (float) $request->query->get('amount');
        }
        if ($request->query->has('currency')) {
            $initialData['currency'] = $request->query->get('currency');
        }

        // Create form
        $form = $this->createForm(TopUpBalanceType::class, $initialData, [
            'currency' => $currency,
            'payment_gateways' => $gatewayChoices,
        ]);

        $form->handleRequest($request);

        // Get amount for events (from form data or query params)
        $amount = $form->has('amount') && $form->get('amount')->getData()
            ? (float) $form->get('amount')->getData()
            : (float) $request->query->get('amount', 0);

        $this->dispatchDataEvent(
            CartTopUpPageAccessedEvent::class,
            $request,
            [$amount, $currency]
        );

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                $paymentUrl = $paymentService->createPayment(
                    $this->getUser(),
                    $formData['amount'],
                    $currency,
                    $formData['voucher'] ?? '',
                    $this->generateUrl('stripe_success', [], 0) . '?session_id={CHECKOUT_SESSION_ID}',
                    $this->generateUrl('stripe_cancel', [], 0),
                    $formData['gateway']
                );

                $this->dispatchDataEvent(
                    CartPaymentRedirectEvent::class,
                    $request,
                    [$formData['amount'], $currency, $paymentUrl]
                );

                return $this->redirect($paymentUrl);
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        $this->dispatchDataEvent(
            CartTopUpDataLoadedEvent::class,
            $request,
            [$amount, $currency]
        );

        $viewData = [
            'form' => $form,
            'availableGateways' => $availableGateways,
            'currency' => $currency,
            'request' => ['amount' => $amount, 'currency' => $currency], // For backward compatibility with template
        ];

        return $this->renderWithEvent(ViewNameEnum::CART_TOPUP, 'panel/cart/topup.html.twig', $viewData, $request);
    }

    #[Route('/cart/configure', name: 'cart_configure')]
    public function configure(Request $request): Response
    {
        $product = $this->getProductByRequest($request);

        $this->dispatchDataEvent(
            CartConfigurePageAccessedEvent::class,
            $request,
            [$product->getId(), $product->getName()]
        );

        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPrices($product);
        $preparedEggs = $this->storeService->getProductEggs($product);
        $requestData = $request->query->all();

        // Prepare egg choices for form
        $eggChoices = [];
        foreach ($preparedEggs as $egg) {
            $eggChoices[$egg['name']] = $egg['id'];
        }

        // Prepare price choices for form
        $priceChoices = [];
        foreach ($product->getPrices() as $price) {
            $priceChoices[$price->getId()] = $price->getId();
        }

        // Prepare initial data from query params
        $initialData = [];
        if (isset($requestData['egg'])) {
            $initialData['egg'] = (int) $requestData['egg'];
        }
        if (isset($requestData['duration'])) {
            $initialData['duration'] = (int) $requestData['duration'];
        }
        if (isset($requestData['slots'])) {
            $initialData['slots'] = (int) $requestData['slots'];
        }

        // Create form
        $form = $this->createForm(ServerOrderType::class, $initialData, [
            'product_id' => $product->getId(),
            'eggs' => $eggChoices,
            'prices' => $priceChoices,
            'has_slot_prices' => $hasSlotPrices,
            'initial_slots' => isset($requestData['slots']) ? (int) $requestData['slots'] : null,
        ]);

        $this->dispatchDataEvent(
            CartConfigureDataLoadedEvent::class,
            $request,
            [$product->getId(), $preparedEggs, $hasSlotPrices]
        );

        $viewData = [
            'product' => $product,
            'eggs' => $preparedEggs,
            'form' => $form,
            'request' => $requestData,
            'isProductAvailable' => $this->storeService->productHasNodeWithResources($product),
            'hasSlotPrices' => $hasSlotPrices,
            'initialSlots' => $requestData['slots'] ?? null,
        ];

        return $this->renderWithEvent(ViewNameEnum::CART_CONFIGURE, 'panel/cart/configure.html.twig', $viewData, $request);
    }

    #[Route('/cart/buy', name: 'cart_buy', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function buy(
        Request $request,
        CreateServerService $createServerService,
    ): Response
    {
        $product = $this->getProductByRequest($request);
        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPrices($product);
        $preparedEggs = $this->storeService->getProductEggs($product);

        // Prepare egg choices for form
        $eggChoices = [];
        foreach ($preparedEggs as $egg) {
            $eggChoices[$egg['name']] = $egg['id'];
        }

        // Prepare price choices for form
        $priceChoices = [];
        foreach ($product->getPrices() as $price) {
            $priceChoices[$price->getId()] = $price->getId();
        }

        // Create and handle form
        $form = $this->createForm(ServerOrderType::class, null, [
            'product_id' => $product->getId(),
            'eggs' => $eggChoices,
            'prices' => $priceChoices,
            'has_slot_prices' => $hasSlotPrices,
            'initial_slots' => null,
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', $this->translator->trans('pteroca.store.invalid_form_data'));
            return $this->redirectToRoute('panel', ['routeName' => 'cart_configure', 'id' => $product->getId()]);
        }

        $formData = $form->getData();
        $eggId = $formData['egg'];
        $priceId = $formData['duration'];
        $serverName = $formData['server-name'];
        $autoRenewal = $formData['auto-renewal'] ?? false;
        $slots = $formData['slots'] ?? null;
        $voucher = $formData['voucher'] ?? '';

        $this->dispatchDataEvent(
            CartBuyRequestedEvent::class,
            $request,
            [$product->getId(), $eggId, $priceId, $serverName, $autoRenewal, $slots]
        );

        try {
            $this->storeService->validateBoughtProduct(
                $product,
                $eggId,
                $priceId,
                null,
                $slots
            );

            $createServerService->createServer(
                $product,
                $eggId,
                $priceId,
                $serverName,
                $autoRenewal,
                $this->getUser(),
                $voucher,
                $slots
            );

            $this->addFlash('success', $this->translator->trans('pteroca.store.successful_purchase'));
        } catch (Exception $exception) {
            $flashMessage = sprintf(
                '%s: %s',
                $this->translator->trans('pteroca.store.error_during_creating_server'),
                $exception->getMessage()
            );
            $this->addFlash('danger', $flashMessage);
        }

        return $this->redirectToRoute('panel', ['routeName' => 'servers']);
    }

    #[Route('/cart/renew', name: 'cart_renew')]
    public function renew(
        Request $request,
    ): Response
    {
        $server = $this->getServerByRequest($request);

        $this->dispatchDataEvent(
            CartRenewPageAccessedEvent::class,
            $request,
            [$server->getId(), $server->getServerProduct()->getName()]
        );

        $isOwner = $server->getUser() === $this->getUser();
        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPricing($server);
        $serverSlots = null;

        if ($hasSlotPrices) {
            $serverSlots = $this->serverSlotPricingService->getServerSlots($server);
        }

        // Create form
        $form = $this->createForm(ServerRenewType::class, null, [
            'server_id' => $server->getId(),
            'current_auto_renewal' => $server->isAutoRenewal(),
            'is_owner' => $isOwner,
            'has_slot_pricing' => $hasSlotPrices,
            'server_slots' => $serverSlots,
        ]);

        $this->dispatchDataEvent(
            CartRenewDataLoadedEvent::class,
            $request,
            [$server->getId(), $isOwner, $hasSlotPrices, $serverSlots]
        );

        $viewData = [
            'server' => $server,
            'form' => $form,
            'isOwner' => $isOwner,
            'hasSlotPrices' => $hasSlotPrices,
            'serverSlots' => $serverSlots,
        ];

        return $this->renderWithEvent(ViewNameEnum::CART_RENEW, 'panel/cart/renew.html.twig', $viewData, $request);
    }

    #[Route('/cart/renew/buy', name: 'cart_renew_buy', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function renewBuy(
        Request $request,
        RenewServerService $renewServerService,
    ): Response
    {
        $server = $this->getServerByRequest($request);
        $isOwner = $server->getUser() === $this->getUser();
        $hasActiveSlotPricing = $this->serverSlotPricingService->hasActiveSlotPricing($server);
        $serverSlots = null;

        if ($hasActiveSlotPricing) {
            $serverSlots = $this->serverSlotPricingService->getServerSlots($server);
        }

        // Create and handle form
        $form = $this->createForm(ServerRenewType::class, null, [
            'server_id' => $server->getId(),
            'current_auto_renewal' => $server->isAutoRenewal(),
            'is_owner' => $isOwner,
            'has_slot_pricing' => $hasActiveSlotPricing,
            'server_slots' => $serverSlots,
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', $this->translator->trans('pteroca.store.invalid_form_data'));
            return $this->redirectToRoute('panel', ['routeName' => 'cart_renew', 'id' => $server->getId()]);
        }

        $formData = $form->getData();
        $voucherCode = $formData['voucher'] ?? '';

        try {
            $this->dispatchDataEvent(
                CartRenewBuyRequestedEvent::class,
                $request,
                [$server->getId(), $voucherCode ?: null, $serverSlots]
            );

            $this->storeService->validateBoughtProduct(
                $server->getServerProduct(),
                null,
                $server->getServerProduct()->getSelectedPrice()->getId(),
                $server,
                $serverSlots
            );

            $renewServerService->renewServer(
                $server,
                $this->getUser(),
                $voucherCode,
                $serverSlots,
            );

            $this->addFlash('success', $this->translator->trans('pteroca.store.successful_purchase'));
        } catch (\Exception $exception) {
            $flashMessage = sprintf(
                '%s: %s',
                $this->translator->trans('pteroca.store.error_during_creating_server'),
                $exception->getMessage()
            );
            $this->addFlash('danger', $flashMessage);
        }

        return $this->redirectToRoute('panel', ['routeName' => 'servers']);
    }

    private function getProductByRequest(Request $request): Product
    {
        $productId = $request->request->getInt('id') ?: $request->query->getInt('id');
        $product = $this->storeService->getActiveProduct($productId);
        if (empty($product)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
        }

        return $product;
    }

    private function getServerByRequest(Request $request): Server
    {
        $serverId = $request->request->getInt('id') ?: $request->query->getInt('id');
        $server = $this->serverRepository->getActiveServer($serverId);

        if (empty($server) || $server->getDeletedAt()) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
        }

        $isOwner = $server->getUser() === $this->getUser() || $this->isGranted(UserRoleEnum::ROLE_ADMIN->value);
        if ($isOwner) {
            return $server;
        }

        $subuser = $this->serverSubuserRepository->findSubuserByServerAndUser($server, $this->getUser());
        if ($subuser) {
            return $server;
        }

        throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
    }
}
