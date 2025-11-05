<?php

namespace App\Core\Service\Crud;

use App\Core\Entity\Product;
use App\Core\Entity\ProductPrice;
use App\Core\Event\Product\ProductCopiedEvent;
use App\Core\Event\Product\ProductCopyRequestedEvent;
use App\Core\Repository\ProductRepository;
use App\Core\Repository\ProductPriceRepository;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ProductCopyService
{
    public function __construct(
        private ProductRepository        $productRepository,
        private ProductPriceRepository   $productPriceRepository,
        private Filesystem               $filesystem,
        private LoggerInterface          $logger,
        private EventDispatcherInterface $eventDispatcher,
        private TranslatorInterface      $translator,
        private string                   $productsDirectory,
        private string                   $projectDir,
    ) {
    }

    public function copyProduct(Product $originalProduct, int $userId, array $context = []): Product
    {
        $requestedEvent = new ProductCopyRequestedEvent(
            $userId,
            $originalProduct->getId(),
            $originalProduct->getName(),
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Product copy operation was blocked';
            throw new RuntimeException($this->translator->trans('pteroca.crud.product.copy_blocked', ['reason' => $reason]));
        }

        $copiedProduct = new Product();
        $copiedProduct->setName($originalProduct->getName() . ' (Copy)');
        $copiedProduct->setDescription($originalProduct->getDescription());
        $copiedProduct->setIsActive(false);
        $copiedProduct->setCategory($originalProduct->getCategory());

        if ($originalProduct->getImagePath()) {
            $copiedImagePath = $this->copyImageFile($originalProduct->getImagePath());
            if ($copiedImagePath) {
                $copiedProduct->setImagePath($copiedImagePath);
            }
        }
        if ($originalProduct->getBannerPath()) {
            $copiedBannerPath = $this->copyImageFile($originalProduct->getBannerPath());
            if ($copiedBannerPath) {
                $copiedProduct->setBannerPath($copiedBannerPath);
            }
        }

        $copiedProduct->setDiskSpace($originalProduct->getDiskSpace());
        $copiedProduct->setMemory($originalProduct->getMemory());
        $copiedProduct->setIo($originalProduct->getIo());
        $copiedProduct->setCpu($originalProduct->getCpu());
        $copiedProduct->setThreads($originalProduct->getThreads());
        $copiedProduct->setDbCount($originalProduct->getDbCount());
        $copiedProduct->setSwap($originalProduct->getSwap());
        $copiedProduct->setBackups($originalProduct->getBackups());
        $copiedProduct->setPorts($originalProduct->getPorts());
        $copiedProduct->setSchedules($originalProduct->getSchedules());
        $copiedProduct->setNodes($originalProduct->getNodes());
        $copiedProduct->setNest($originalProduct->getNest());
        $copiedProduct->setEggs($originalProduct->getEggs());
        $copiedProduct->setEggsConfiguration($originalProduct->getEggsConfiguration());
        $copiedProduct->setAllowChangeEgg($originalProduct->getAllowChangeEgg());

        $this->productRepository->save($copiedProduct, true);

        $pricesCount = 0;
        foreach ($originalProduct->getPrices() as $originalPrice) {
            $copiedPrice = new ProductPrice();

            $copiedPrice->setValue($originalPrice->getValue());
            $copiedPrice->setType($originalPrice->getType());
            $copiedPrice->setUnit($originalPrice->getUnit());
            $copiedPrice->setPrice($originalPrice->getPrice());

            $copiedProduct->addPrice($copiedPrice);
            $this->productPriceRepository->save($copiedPrice);
            $pricesCount++;
        }
        $this->productPriceRepository->flush();

        $copiedEvent = new ProductCopiedEvent(
            $userId,
            $originalProduct->getId(),
            $copiedProduct->getId(),
            $copiedProduct->getName(),
            $pricesCount,
            $context
        );
        $this->eventDispatcher->dispatch($copiedEvent);

        return $copiedProduct;
    }

    private function copyImageFile(string $originalImagePath): ?string
    {
        $originalFilePath = $this->prepareImagePath($originalImagePath);
        
        if (!$this->filesystem->exists($originalFilePath)) {
            return null;
        }
        
        $pathInfo = pathinfo($originalImagePath);
        $timestamp = time();
        $newFileName = $pathInfo['filename'] . '-copy-' . $timestamp . '.' . $pathInfo['extension'];
        $newFilePath = $this->prepareImagePath($newFileName);
        
        try {
            $this->filesystem->copy($originalFilePath, $newFilePath);
            return $newFileName;
        } catch (Exception $e) {
            $this->logger->error('Failed to copy product image file', [
                'original_path' => $originalImagePath,
                'new_path' => $newFileName,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            
            return null;
        }
    }

    private function prepareImagePath(string $imageName): string
    {
        $preparedImagePath = sprintf('%s/%s/%s', $this->projectDir, $this->productsDirectory, $imageName);

        return str_replace('/', DIRECTORY_SEPARATOR, $preparedImagePath);
    }
}
