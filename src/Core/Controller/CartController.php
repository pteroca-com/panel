<?php

namespace App\Core\Controller;

use App\Core\Entity\Product;
use App\Core\Service\Authorization\UserVerificationService;
use App\Core\Service\Server\CreateServerService;
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
        private readonly TranslatorInterface $translator,
    ) {}

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
        try {
            $userVerificationService->validateUserVerification($this->getUser());
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('panel', ['routeName' => 'store_product', 'id' => $product->getId()]);
        }

        $product = $this->getProductByRequest($request);
        $eggId = $request->request->getInt('egg');
        $priceId = $request->request->getInt('duration');
        $serverName = $request->request->getString('server-name');
        $autoRenewal = $request->request->getBoolean('auto-renewal');

        try {
            $this->storeService->validateBoughtProduct($this->getUser(), $product, $eggId, $priceId);
            $createServerService->createServer($product, $eggId, $priceId, $serverName, $autoRenewal, $this->getUser());
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

    public function renew(
        Request $request,
    ): Response
    {

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
}
