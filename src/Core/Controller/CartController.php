<?php

namespace App\Core\Controller;

use App\Core\Attribute\RequiresVerifiedEmail;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Event\Cart\CartTopUpPageAccessedEvent;
use App\Core\Event\Cart\CartTopUpDataLoadedEvent;
use App\Core\Event\Cart\CartPaymentRedirectEvent;
use App\Core\Event\Cart\CartConfigurePageAccessedEvent;
use App\Core\Event\Cart\CartConfigureDataLoadedEvent;
use App\Core\Event\Cart\CartBuyRequestedEvent;
use App\Core\Event\Cart\CartRenewPageAccessedEvent;
use App\Core\Event\Cart\CartRenewDataLoadedEvent;
use App\Core\Event\Cart\CartRenewBuyRequestedEvent;
use App\Core\Event\View\ViewDataEvent;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\Server\CreateServerService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\Server\ServerSlotPricingService;
use App\Core\Service\SettingService;
use App\Core\Service\StoreService;
use App\Core\Trait\EventContextTrait;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartController extends AbstractController
{
    use EventContextTrait;

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
    ): Response
    {
        $requestPayload = $request->isMethod('POST')
            ? $request->request->all()
            : $request->query->all();
        $currency = $settingService->getSetting(SettingEnum::CURRENCY_NAME->value);
        $context = $this->buildMinimalEventContext($request);
        $isRequestInvalid = empty($requestPayload['currency'])
            || empty($requestPayload['amount'])
            || strtolower($currency) !== strtolower($requestPayload['currency']);
        $amount = (float) ($requestPayload['amount'] ?? 0);

        if (!$isRequestInvalid && $amount <= 0) {
            $this->addFlash('danger', $this->translator->trans('pteroca.recharge.amount_must_be_positive'));
            return $this->redirectToRoute('panel', [
                'routeName' => 'recharge_balance',
            ]);
        }
        
        $this->dispatchEvent(new CartTopUpPageAccessedEvent(
            $this->getUser()->getId(),
            $amount,
            $currency,
            $context
        ));

        if ($request->isMethod('POST')) {
            try {
                $paymentUrl = $paymentService->createPayment(
                    $this->getUser(),
                    $requestPayload['amount'],
                    $currency,
                    $requestPayload['voucher'] ?? '',
                    $this->generateUrl('stripe_success', [], 0) . '?session_id={CHECKOUT_SESSION_ID}',
                    $this->generateUrl('stripe_cancel', [], 0)
                );
                
                $this->dispatchEvent(new CartPaymentRedirectEvent(
                    $this->getUser()->getId(),
                    $amount,
                    $currency,
                    $paymentUrl,
                    $context
                ));

                return $this->redirect($paymentUrl);
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        } else {
            $this->dispatchEvent(new CartTopUpDataLoadedEvent(
                $this->getUser()->getId(),
                $amount,
                $currency,
                $context
            ));
        }
        
        $viewData = [
            'request' => $requestPayload,
        ];
        
        $viewEvent = $this->dispatchEvent(new ViewDataEvent(
            'cart_topup',
            $viewData,
            $this->getUser(),
            $context
        ));

        return $this->render('panel/cart/topup.html.twig', $viewEvent->getViewData());
    }

    #[Route('/cart/configure', name: 'cart_configure')]
    public function configure(Request $request): Response
    {
        $product = $this->getProductByRequest($request);

        $context = $this->buildMinimalEventContext($request);
        $this->dispatchEvent(new CartConfigurePageAccessedEvent(
            $this->getUser()->getId(),
            $product->getId(),
            $product->getName(),
            $context
        ));
        
        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPrices($product);
        $preparedEggs = $this->storeService->getProductEggs($product);
        $requestData = $request->query->all();
    
        $this->dispatchEvent(new CartConfigureDataLoadedEvent(
            $this->getUser()->getId(),
            $product->getId(),
            $preparedEggs,
            $hasSlotPrices,
            $context
        ));
        
        $viewData = [
            'product' => $product,
            'eggs' => $preparedEggs,
            'request' => $requestData,
            'isProductAvailable' => $this->storeService->productHasNodeWithResources($product),
            'hasSlotPrices' => $hasSlotPrices,
            'initialSlots' => $requestData['slots'] ?? null,
        ];
        
        $viewEvent = $this->dispatchEvent(new ViewDataEvent(
            'cart_configure',
            $viewData,
            $this->getUser(),
            $context
        ));

        return $this->render('panel/cart/configure.html.twig', $viewEvent->getViewData());
    }

    #[Route('/cart/buy', name: 'cart_buy', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function buy(
        Request $request,
        CreateServerService $createServerService,
    ): Response
    {
        $product = $this->getProductByRequest($request);

        $eggId = $request->request->getInt('egg');
        $priceId = $request->request->getInt('duration');
        $serverName = $request->request->getString('server-name');
        $autoRenewal = $request->request->getBoolean('auto-renewal');
        $slots = $request->request->get('slots') ? $request->request->getInt('slots') : null;

        $context = $this->buildMinimalEventContext($request);
        $this->dispatchEvent(new CartBuyRequestedEvent(
            $this->getUser()->getId(),
            $product->getId(),
            $eggId,
            $priceId,
            $serverName,
            $autoRenewal,
            $slots,
            $context
        ));

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
                $request->request->getString('voucher'),
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
        
        $context = $this->buildMinimalEventContext($request);
        $this->dispatchEvent(new CartRenewPageAccessedEvent(
            $this->getUser()->getId(),
            $server->getId(),
            $server->getServerProduct()->getName(),
            $context
        ));
        
        $isOwner = $server->getUser() === $this->getUser();
        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPricing($server);

        if ($hasSlotPrices) {
            $serverSlots = $this->serverSlotPricingService->getServerSlots($server);
        }
        
        $this->dispatchEvent(new CartRenewDataLoadedEvent(
            $this->getUser()->getId(),
            $server->getId(),
            $isOwner,
            $hasSlotPrices,
            $serverSlots ?? null,
            $context
        ));
        
        $viewData = [
            'server' => $server,
            'isOwner' => $isOwner,
            'hasSlotPrices' => $hasSlotPrices,
            'serverSlots' => $serverSlots ?? null,
        ];
        
        $viewEvent = $this->dispatchEvent(new ViewDataEvent(
            'cart_renew',
            $viewData,
            $this->getUser(),
            $context
        ));

        return $this->render('panel/cart/renew.html.twig', $viewEvent->getViewData());
    }

    #[Route('/cart/renew/buy', name: 'cart_renew_buy', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function renewBuy(
        Request $request,
        RenewServerService $renewServerService,
    ): Response
    {
        $server = $this->getServerByRequest($request);
        $voucherCode = $request->request->getString('voucher');
        $context = $this->buildMinimalEventContext($request);

        try {
            $hasActiveSlotPricing = $this->serverSlotPricingService->hasActiveSlotPricing($server);
            if ($hasActiveSlotPricing) {
                $serverSlots = $this->serverSlotPricingService->getServerSlots($server);
            }
            
            $this->dispatchEvent(new CartRenewBuyRequestedEvent(
                $this->getUser()->getId(),
                $server->getId(),
                $voucherCode ?: null,
                $serverSlots ?? null,
                $context
            ));

            $this->storeService->validateBoughtProduct(
                $server->getServerProduct(),
                null,
                $server->getServerProduct()->getSelectedPrice()->getId(),
                $server,
                $serverSlots ?? null
            );

            $renewServerService->renewServer(
                $server,
                $this->getUser(),
                $voucherCode,
                $serverSlots ?? null,
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
