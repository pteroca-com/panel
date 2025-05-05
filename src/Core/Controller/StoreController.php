<?php

namespace App\Core\Controller;

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
}
