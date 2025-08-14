<?php

namespace App\Core\Service\Crud;

use App\Core\Entity\Product;
use App\Core\Repository\ProductRepository;
use App\Core\Repository\ProductPriceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Core\Entity\ProductPrice;

class ProductCopyService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductPriceRepository $productPriceRepository,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        private readonly string $productsDirectory,
        private readonly string $projectDir,
    ) {
    }

    public function copyProduct(Product $originalProduct): Product
    {
        $copiedProduct = new Product();
        $copiedProduct->setName($originalProduct->getName() . ' (Copy)');
        $copiedProduct->setDescription($originalProduct->getDescription());
        $copiedProduct->setIsActive(false); // Domyślnie nieaktywny
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
        
        foreach ($originalProduct->getPrices() as $originalPrice) {
            $copiedPrice = new ProductPrice();

            $copiedPrice->setValue($originalPrice->getValue());
            $copiedPrice->setType($originalPrice->getType());
            $copiedPrice->setUnit($originalPrice->getUnit());
            $copiedPrice->setPrice($originalPrice->getPrice());
            
            $copiedProduct->addPrice($copiedPrice);
            $this->productPriceRepository->save($copiedPrice);
        }
        $this->productPriceRepository->flush();
        
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
        } catch (\Exception $e) {
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
