<?php

namespace App\Core\Controller;

use App\Core\Attribute\RequiresVerifiedEmail;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\PurchaseTokenService;
use App\Core\Service\Server\CreateServerService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\Server\ServerSlotPricingService;
use App\Core\Service\SettingService;
use App\Core\Service\StoreService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartController extends AbstractController
{
    public function __construct(
        private readonly StoreService $storeService,
        private readonly ServerRepository $serverRepository,
        private readonly ServerSubuserRepository $serverSubuserRepository,
        private readonly TranslatorInterface $translator,
        private readonly ServerSlotPricingService $serverSlotPricingService,
        private readonly PurchaseTokenService $purchaseTokenService,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
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

        if (
            empty($requestPayload['currency'])
            || empty($requestPayload['amount'])
            || strtolower($currency) !== strtolower($requestPayload['currency'])
        ) {
            return $this->redirectToRoute('panel', [
                'routeName' => 'recharge_balance',
            ]);
        }

        $amount = (float) $requestPayload['amount'];
        if ($amount <= 0) {
            $this->addFlash('danger', $this->translator->trans('pteroca.recharge.amount_must_be_positive'));
            return $this->redirectToRoute('panel', [
                'routeName' => 'recharge_balance',
            ]);
        }

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

                return $this->redirect($paymentUrl);
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('panel/cart/topup.html.twig', [
            'request' => $requestPayload,
        ]);
    }

    #[Route('/cart/configure', name: 'cart_configure')]
    public function configure(Request $request): Response
    {
        $product = $this->getProductByRequest($request);
        $preparedEggs = $this->storeService->getProductEggs($product);
        $requestParams = $request->query->all();
        
        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPrices($product);
        $purchaseToken = $this->purchaseTokenService->generateToken($this->getUser(), 'buy');

        return $this->render('panel/cart/configure.html.twig', [
            'product' => $product,
            'eggs' => $preparedEggs,
            'request' => $requestParams,
            'isProductAvailable' => $this->storeService->productHasNodeWithResources($product),
            'hasSlotPrices' => $hasSlotPrices,
            'initialSlots' => $requestParams['slots'] ?? null,
            'purchase_token' => $purchaseToken,
        ]);
    }

    #[Route('/cart/buy', name: 'cart_buy', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function buy(
        Request $request,
        CreateServerService $createServerService,
    ): Response
    {
        try {
            $disableCsrf = isset($_ENV['DISABLE_CSRF']) && $_ENV['DISABLE_CSRF'] === 'true';
            if (!$disableCsrf) {
                $csrfToken = $request->request->get('_csrf_token');
                if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('submit', $csrfToken))) {
                    throw new \Exception($this->translator->trans('pteroca.error.invalid_csrf_token'));
                }
            }

            $purchaseToken = $request->request->getString('purchase_token');
            $this->purchaseTokenService->validateAndConsumeToken($purchaseToken, $this->getUser(), 'buy');

            $product = $this->getProductByRequest($request);
            $eggId = $request->request->getInt('egg');
            $priceId = $request->request->getInt('duration');
            $serverName = $request->request->getString('server-name');
            $autoRenewal = $request->request->getBoolean('auto-renewal');
            $slots = $request->request->get('slots') ? $request->request->getInt('slots') : null;
            $voucherCode = $request->request->getString('voucher');

            $createdServer = null;
            $this->entityManager->wrapInTransaction(function() use (
                $product, $eggId, $priceId, $serverName, $autoRenewal, $slots, $voucherCode, $createServerService, &$createdServer
            ) {
                $lockedUser = $this->userRepository->findOneByIdWithLock($this->getUser()->getId());

                if (!$lockedUser) {
                    throw new \Exception($this->translator->trans('pteroca.error.user_not_found'));
                }

                $this->storeService->validateBoughtProduct(
                    $product,
                    $eggId,
                    $priceId,
                    null,
                    $slots
                );

                $createdServer = $createServerService->createServer(
                    $product,
                    $eggId,
                    $priceId,
                    $serverName,
                    $autoRenewal,
                    $lockedUser,
                    $voucherCode,
                    $slots
                );
            });

            $this->addFlash('success', $this->translator->trans('pteroca.store.successful_purchase'));

            if ($createdServer) {
                return $this->redirectToRoute('panel', [
                    'routeName' => 'server',
                    'id' => $createdServer->getPterodactylServerIdentifier()
                ]);
            }
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
        $isOwner = $server->getUser() === $this->getUser();
        $hasSlotPrices = $this->serverSlotPricingService->hasSlotPricing($server);

        if ($hasSlotPrices) {
            $serverSlots = $this->serverSlotPricingService->getServerSlots($server);
        }

        // Generate one-time purchase token to prevent double-submit
        $purchaseToken = $this->purchaseTokenService->generateToken($this->getUser(), 'renew');

        return $this->render('panel/cart/renew.html.twig', [
            'server' => $server,
            'isOwner' => $isOwner,
            'hasSlotPrices' => $hasSlotPrices,
            'serverSlots' => $serverSlots ?? null,
            'purchase_token' => $purchaseToken,
        ]);
    }

    #[Route('/cart/renew/buy', name: 'cart_renew_buy', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function renewBuy(
        Request $request,
        RenewServerService $renewServerService,
    ): Response
    {
        try {
            $disableCsrf = isset($_ENV['DISABLE_CSRF']) && $_ENV['DISABLE_CSRF'] === 'true';
            if (!$disableCsrf) {
                $csrfToken = $request->request->get('_csrf_token');
                if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('submit', $csrfToken))) {
                    throw new \Exception($this->translator->trans('pteroca.error.invalid_csrf_token'));
                }
            }

            $purchaseToken = $request->request->getString('purchase_token');
            $this->purchaseTokenService->validateAndConsumeToken($purchaseToken, $this->getUser(), 'renew');

            $server = $this->getServerByRequest($request);
            $voucherCode = $request->request->getString('voucher');

            $serverSlots = null;
            $hasActiveSlotPricing = $this->serverSlotPricingService->hasActiveSlotPricing($server);
            if ($hasActiveSlotPricing) {
                $serverSlots = $this->serverSlotPricingService->getServerSlots($server);
            }

            $this->entityManager->wrapInTransaction(function() use (
                $server, $voucherCode, $serverSlots, $renewServerService
            ) {
                $lockedUser = $this->userRepository->findOneByIdWithLock($this->getUser()->getId());

                if (!$lockedUser) {
                    throw new \Exception($this->translator->trans('pteroca.error.user_not_found'));
                }

                $this->storeService->validateBoughtProduct(
                    $server->getServerProduct(),
                    null,
                    $server->getServerProduct()->getSelectedPrice()->getId(),
                    $server,
                    $serverSlots,
                );

                $renewServerService->renewServer(
                    $server,
                    $lockedUser,
                    $voucherCode,
                    $serverSlots,
                );
            });

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
