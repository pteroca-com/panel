<?php

namespace App\Core\Controller;

use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\Server\CreateServerService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\Server\ServerService;
use App\Core\Service\StoreService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StoreController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly StoreService $storeService,
    ) {}

    #[Route('/store', name: 'store')]
    public function store(): Response
    {
        $this->checkPermission();
        return $this->render('panel/store/index.html.twig', [
            'categories' => $this->storeService->getCategories(),
            'products' => $this->storeService->getCategoryProducts(),
        ]);
    }

    #[Route('/store/category', name: 'store_category')]
    public function category(Request $request): Response
    {
        $this->checkPermission();
        $categoryId = $request->query->getInt('id');
        $category = $this->storeService->getCategory($categoryId);
        if (empty($category)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.category_not_found'));
        }

        $products = $this->storeService->getCategoryProducts($category);

        return $this->render('panel/store/list.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    #[Route('/store/product', name: 'store_product')]
    public function product(Request $request): Response
    {
        $this->checkPermission();
        $productId = $request->query->getInt('id');
        $product = $this->storeService->getActiveProduct($productId);
        if (empty($product)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
        }

        $product = $this->storeService->prepareProduct($product);
        $preparedEggs = $this->storeService->getProductEggs($product);

        return $this->render('panel/store/product.html.twig', [
            'product' => $product,
            'eggs' => $preparedEggs,
        ]);
    }

    #[Route('/store/server/renew', name: 'store_server_renew')]
    public function renewProduct(Request $request, ServerService $serverService): Response
    {
        $this->checkPermission();
        $serverId = $request->query->getString('id');
        $server = $serverService->getServer($serverId);
        if (empty($server)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
        }

        $originalProduct = $server->getServerProduct()->getOriginalProduct();
        if (!empty($originalProduct) && $originalProduct->getIsActive()) {
            $product = $this->storeService->prepareProduct($server->getServerProduct()->getOriginalProduct());
        } else {
            $product = $server->getServerProduct();
        }

        return $this->render('panel/store/renew.html.twig', [
            'product' => $product,
            'server' => $server,
            'serverDetails' => $serverService->getServerDetails($server),
            'selectedPrice' => $server->getServerProduct()->getSelectedPrice(),
        ]);
    }

    #[Route('/store/product/buy', name: 'store_product_buy', methods: ['POST'])]
    public function buy(
        Request $request,
        CreateServerService $createServerService,
        RenewServerService $renewServerService,
        UserVerificationService $userVerificationService,
        ServerService $serverService,
    ): Response {
        $this->checkPermission();
        $serverId = $request->query->getString('server');
        if (!empty($serverId)) {
            $server = $serverService->getServer($serverId);

            if (empty($server)) {
                throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
            }

            $productId = $server->getServerProduct()->getId();
        }

        $productId = $productId ?? $request->request->getInt('product');
        $eggId = $request->request->getInt('egg');
        $priceId = $request->request->getInt('duration');

        try {
            $userVerificationService->validateUserVerification($this->getUser());
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('panel', ['routeName' => 'store_product', 'id' => $productId]);
        }

        try {
            if (empty($server)) {
                $product = $this->storeService->getActiveProduct($productId);
                if (empty($product)) {
                    throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
                }

                $this->storeService->validateBoughtProduct($this->getUser(), $product, $eggId, $priceId);
                $createServerService->createServer($product, $eggId, $priceId, $this->getUser());
            } else {
                $renewServerService->renewServer($server, $this->getUser());
            }

            $this->addFlash('success', $this->translator->trans('pteroca.store.successful_purchase'));
        } catch (\Exception $e) {
            $flashMessage = sprintf(
                '%s: %s',
                $this->translator->trans('pteroca.store.error_during_creating_server'),
                $e->getMessage()
            );
            $this->addFlash('danger', $flashMessage);
        }

        if (!empty($server)) {
            return $this->redirectToRoute('panel', ['routeName' => 'servers']);
        }

        return $this->redirectToRoute('panel', ['routeName' => 'store_product', 'id' => $productId]);
    }
}
