<?php

namespace App\Core\Service;

use App\Core\Contract\ProductInterface;
use App\Core\Contract\ProductPriceInterface;
use App\Core\Contract\UserInterface;
use App\Core\Entity\Category;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
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
            'deletedAt' => null,
        ]));
    }

    public function getActiveProduct(int $productId): ?Product
    {
        $product = $this->productRepository->find($productId);

        if (empty($product) || $product->getIsActive() === false || $product->getDeletedAt() !== null) {
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
        $eggs = $this->pterodactylService
            ->getApi()
            ->nest_eggs
            ->all($product->getNest())
            ->toArray();

        return array_filter($eggs, fn($egg) => in_array($egg->id, $product->getEggs()));
    }

    public function productHasNodeWithResources(Product $product): bool
    {
        foreach ($product->getNodes() as $node) {
            $node = $this->pterodactylService
                ->getApi()
                ->nodes
                ->get($node)
                ->toArray();

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

    public function validateBoughtProduct(
        ProductInterface $product,
        ?int $eggId,
        int $priceId,
        ?Server $server = null,
        ?int $slots = null
    ): void
    {
        if (empty($server) && (empty($eggId) || !in_array($eggId, $product->getEggs()))) {
            throw new NotFoundHttpException($this->translator->trans('pteroca.store.egg_not_found'));
        }

        $productPrices = $product->getPrices()->toArray();
        $price = array_filter($productPrices, fn($price) => $price->getId() === $priceId);
        if (empty($price)) {
            throw new NotFoundHttpException($this->translator->trans('pteroca.store.price_not_found'));
        }

        $selectedPrice = current($price);
        if ($selectedPrice->getType()->value === 'slot') {
            if (empty($slots) || $slots < 1) {
                throw new NotFoundHttpException($this->translator->trans('pteroca.store.invalid_slots_number'));
            }

            if (!empty($eggId)) {
                $eggsConfigurationJson = $product->getEggsConfiguration();
                $maxSlots = 32; // default value
                
                if ($eggsConfigurationJson) {
                    try {
                        $eggsConfiguration = json_decode($eggsConfigurationJson, true, 512, JSON_THROW_ON_ERROR);
                        if (isset($eggsConfiguration[$eggId]['variables'])) {
                            foreach ($eggsConfiguration[$eggId]['variables'] as $variable) {
                                if (isset($variable['slot_variable']) && $variable['slot_variable'] === 'on' && !empty($variable['value'])) {
                                    $maxSlots = (int) $variable['value'];
                                    break;
                                }
                            }
                        }
                    } catch (\JsonException) {
                        // If JSON is invalid, use default maxSlots
                    }
                }

                if ($slots > $maxSlots) {
                    throw new NotFoundHttpException(
                        $this->translator->trans('pteroca.store.slots_exceed_maximum', ['max' => $maxSlots])
                    );
                }
            }
        }
    }

    public function validateUserBalanceByPrice(UserInterface $user, ProductPriceInterface $selectedPrice, ?int $slots = null): void
    {
        $finalPrice = $selectedPrice->getPrice();
        
        if ($selectedPrice->getType()->value === 'slot' && $slots !== null && $slots > 0) {
            $finalPrice = $selectedPrice->getPrice() * $slots;
        }
        
        if ($finalPrice > $user->getBalance()) {
            throw new \Exception($this->translator->trans('pteroca.store.not_enough_funds'));
        }
    }
}
