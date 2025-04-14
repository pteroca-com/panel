<?php

namespace App\Core\Controller;

use App\Core\Service\StoreService;
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
        $productId = $request->query->getInt('id');
        $product = $this->storeService->getActiveProduct($productId);
        if (empty($product)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
        }

        $preparedEggs = $this->storeService->getProductEggs($product);
        $request = $request->query->all();

        return $this->render('panel/cart/configure.html.twig', [
            'product' => $product,
            'eggs' => $preparedEggs,
            'request' => $request,
        ]);
    }
}