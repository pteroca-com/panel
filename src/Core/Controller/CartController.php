<?php

namespace App\Core\Controller;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\ServerSubuserRepository;
use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\Payment\PaymentService;
use App\Core\Service\Server\CreateServerService;
use App\Core\Service\Server\RenewServerService;
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
    ) {}

    #[Route('/cart/topup', name: 'cart_topup', methods: ['GET', 'POST'])]
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
        $request = $request->query->all();

        return $this->render('panel/cart/configure.html.twig', [
            'product' => $product,
            'eggs' => $preparedEggs,
            'request' => $request,
            'isProductAvailable' => $this->storeService->productHasNodeWithResources($product),
        ]);
    }

    #[Route('/cart/buy', name: 'cart_buy', methods: ['POST'])]
    public function buy(
        Request $request,
        UserVerificationService $userVerificationService,
        CreateServerService $createServerService,
    ): Response
    {
        $product = $this->getProductByRequest($request);

        try {
            $userVerificationService->validateUserVerification($this->getUser());
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('panel', ['routeName' => 'store_product', 'id' => $product->getId()]);
        }

        $eggId = $request->request->getInt('egg');
        $priceId = $request->request->getInt('duration');
        $serverName = $request->request->getString('server-name');
        $autoRenewal = $request->request->getBoolean('auto-renewal');

        try {
            $this->storeService->validateBoughtProduct(
                $product,
                $eggId,
                $priceId
            );

            $createServerService->createServer(
                $product,
                $eggId,
                $priceId,
                $serverName,
                $autoRenewal,
                $this->getUser(),
                $request->request->getString('voucher'),
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
        $isOwner = $server->getUser() === $this->getUser();

        return $this->render('panel/cart/renew.html.twig', [
            'server' => $server,
            'isOwner' => $isOwner,
        ]);
    }

    #[Route('/cart/renew/buy', name: 'cart_renew_buy', methods: ['POST'])]
    public function renewBuy(
        Request $request,
        UserVerificationService $userVerificationService,
        RenewServerService $renewServerService,
    ): Response
    {
        $server = $this->getServerByRequest($request);

        try {
            $userVerificationService->validateUserVerification($this->getUser());
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('panel', ['routeName' => 'cart_renew', 'id' => $server->getId()]);
        }

        try {
            $this->storeService->validateBoughtProduct(
                $server->getServerProduct(),
                null,
                $server->getServerProduct()->getSelectedPrice()->getId(),
                $server,
            );

            $renewServerService->renewServer(
                $server,
                $this->getUser(),
                $request->request->getString('voucher'),
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
