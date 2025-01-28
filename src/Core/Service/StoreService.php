<?php

namespace App\Core\Service;

use App\Core\Entity\Category;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Repository\CategoryRepository;
use App\Core\Repository\ProductRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class StoreService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly PterodactylService $pterodactylService,
        private readonly TranslatorInterface $translator,
        private readonly string $categoriesBasePath,
        private readonly string $productsBasePath,
    ) {}

    public function getCategories(): array
    {
        $imagePath = $this->categoriesBasePath . '/';
        return array_map(function ($category) use ($imagePath) {
            if (!empty($category->getImagePath())) {
                $category->setImagePath( $imagePath . $category->getImagePath());
            }
            return $category;
        }, $this->categoryRepository->findAll());
    }

    public function getCategory(int $categoryId): ?Category
    {
        return $this->categoryRepository->find($categoryId);
    }

    public function getCategoryProducts(?Category $category = null): array
    {
        $imagePath = $this->productsBasePath . '/';
        return array_map(function ($product) use ($imagePath) {
            if (!empty($product->getImagePath())) {
                $product->setImagePath($imagePath . $product->getImagePath());
            }
            return $product;
        }, $this->productRepository->findBy([
            'category' => $category,
            'isActive' => true,
        ]));
    }

    public function getActiveProduct(int $productId): ?Product
    {
        $product = $this->productRepository->find($productId);
        if (empty($product) || $product->getIsActive() === false) {
            return null;
        }
        return $product;
    }

    public function prepareProduct(Product $product): Product
    {
        if (!empty($product->getImagePath())) {
            $imagePath = $this->productsBasePath . '/';
            $product->setImagePath($imagePath . $product->getImagePath());
        }

        if (!empty($product->getBannerPath())) {
            $bannerPath = $this->productsBasePath . '/';
            $product->setBannerPath($bannerPath . $product->getBannerPath());
        }

        return $product;
    }

    public function getProductEggs(Product $product): array
    {
        $eggs = $this->pterodactylService->getApi()->nest_eggs->all($product->getNest())->toArray();
        return array_filter($eggs, fn($egg) => in_array($egg->id, $product->getEggs()));
    }

    public function productHasNodeWithResources(Product $product): bool
    {
        foreach ($product->getNodes() as $node) {
            $node = $this->pterodactylService->getApi()->nodes->get($node)->toArray();
            if ($this->checkNodeResources($product->getMemory(), $product->getDiskSpace(), $node)) {
                return true;
            }
        }
        return false;
    }

    public function checkNodeResources(int $requiredMemory, int $requiredDisk, array $nodeData): bool
    {
        $totalMemory = $nodeData['memory'] + ($nodeData['memory'] * $nodeData['memory_overallocate'] / 100);
        $totalDisk = $nodeData['disk'] + ($nodeData['disk'] * $nodeData['disk_overallocate'] / 100);

        if (!isset($nodeData['allocated_resources'])) {
            return false;
        }

        $allocatedMemory = $nodeData['allocated_resources']['memory'] ?? 0;
        $allocatedDisk = $nodeData['allocated_resources']['disk'] ?? 0;

        $availableMemory = $totalMemory - $allocatedMemory;
        $availableDisk = $totalDisk - $allocatedDisk;

        if ($availableMemory >= $requiredMemory && $availableDisk >= $requiredDisk) {
            return true;
        }

        return false;
    }

    public function validateBoughtProduct(User $user, Product $product, int $eggId, ?Server $server = null): void
    {
        if (empty($server) && !in_array($eggId, $product->getEggs())) {
            throw new NotFoundHttpException($this->translator->trans('pteroca.store.egg_not_found'));
        }


        if (($product->getPrice() / 100) > $user->getBalance()) {
            throw new NotFoundHttpException($this->translator->trans('pteroca.store.not_enough_funds'));
        }
    }
}
