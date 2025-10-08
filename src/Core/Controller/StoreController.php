<?php

namespace App\Core\Controller;

use App\Core\Event\Store\StoreAccessedEvent;
use App\Core\Event\Store\StoreDataLoadedEvent;
use App\Core\Event\Store\StoreCategoryAccessedEvent;
use App\Core\Event\Store\StoreCategoryDataLoadedEvent;
use App\Core\Event\Store\StoreProductViewedEvent;
use App\Core\Event\Store\StoreProductDataLoadedEvent;
use App\Core\Event\View\ViewDataEvent;
use App\Core\Service\StoreService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StoreController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly StoreService $storeService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[Route('/store', name: 'store')]
    public function store(Request $request): Response
    {
        $this->checkPermission();
        
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
        ];
        
        // 1. Emit StoreAccessedEvent
        $accessedEvent = new StoreAccessedEvent(
            $this->getUser()?->getId(),
            $context
        );
        $this->eventDispatcher->dispatch($accessedEvent);
        
        // Pobierz dane
        $categories = $this->storeService->getCategories();
        $products = $this->storeService->getCategoryProducts();
        
        // 2. Emit StoreDataLoadedEvent
        $dataLoadedEvent = new StoreDataLoadedEvent(
            $this->getUser()?->getId(),
            $categories,
            $products,
            count($categories),
            count($products),
            $context
        );
        $this->eventDispatcher->dispatch($dataLoadedEvent);
        
        // 3. Przygotuj dane widoku
        $viewData = [
            'categories' => $categories,
            'products' => $products,
        ];
        
        // 4. Emit ViewDataEvent
        $viewEvent = new ViewDataEvent(
            'store_index',
            $viewData,
            $this->getUser(),
            $context
        );
        $this->eventDispatcher->dispatch($viewEvent);
        
        return $this->render('panel/store/index.html.twig', $viewEvent->getViewData());
    }

    #[Route('/store/category', name: 'store_category')]
    public function category(Request $request): Response
    {
        $this->checkPermission();
        $categoryId = $request->query->getInt('id');
        
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
        ];
        
        $category = $this->storeService->getCategory($categoryId);
        if (empty($category)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.category_not_found'));
        }
        
        // 1. Emit StoreCategoryAccessedEvent
        $categoryAccessedEvent = new StoreCategoryAccessedEvent(
            $this->getUser()?->getId(),
            $categoryId,
            $category->getName(),
            $context
        );
        $this->eventDispatcher->dispatch($categoryAccessedEvent);
        
        $products = $this->storeService->getCategoryProducts($category);
        
        // 2. Emit StoreCategoryDataLoadedEvent
        $dataLoadedEvent = new StoreCategoryDataLoadedEvent(
            $this->getUser()?->getId(),
            $categoryId,
            $products,
            count($products),
            $context
        );
        $this->eventDispatcher->dispatch($dataLoadedEvent);
        
        // 3. Przygotuj dane widoku
        $viewData = [
            'category' => $category,
            'products' => $products,
        ];
        
        // 4. Emit ViewDataEvent
        $viewEvent = new ViewDataEvent(
            'store_category',
            $viewData,
            $this->getUser(),
            $context
        );
        $this->eventDispatcher->dispatch($viewEvent);
        
        return $this->render('panel/store/list.html.twig', $viewEvent->getViewData());
    }

    #[Route('/store/product', name: 'store_product')]
    public function product(Request $request): Response
    {
        $this->checkPermission();
        $productId = $request->query->getInt('id');
        
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
        ];
        
        $product = $this->storeService->getActiveProduct($productId);
        if (empty($product)) {
            throw $this->createNotFoundException($this->translator->trans('pteroca.store.product_not_found'));
        }
        
        $product = $this->storeService->prepareProduct($product);
        
        // 1. Emit StoreProductViewedEvent (po prepareProduct)
        $productViewedEvent = new StoreProductViewedEvent(
            $this->getUser()?->getId(),
            $productId,
            $product->getName(),
            $product->getPrices(),
            $context
        );
        $this->eventDispatcher->dispatch($productViewedEvent);
        
        $preparedEggs = $this->storeService->getProductEggs($product);
        
        // 2. Emit StoreProductDataLoadedEvent
        $dataLoadedEvent = new StoreProductDataLoadedEvent(
            $this->getUser()?->getId(),
            $productId,
            $product,
            $preparedEggs,
            count($preparedEggs),
            $context
        );
        $this->eventDispatcher->dispatch($dataLoadedEvent);
        
        // 3. Przygotuj dane widoku
        $viewData = [
            'product' => $product,
            'eggs' => $preparedEggs,
        ];
        
        // 4. Emit ViewDataEvent
        $viewEvent = new ViewDataEvent(
            'store_product',
            $viewData,
            $this->getUser(),
            $context
        );
        $this->eventDispatcher->dispatch($viewEvent);
        
        return $this->render('panel/store/product.html.twig', $viewEvent->getViewData());
    }
}
