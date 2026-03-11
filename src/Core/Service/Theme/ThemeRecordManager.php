<?php

namespace App\Core\Service\Theme;

use App\Core\Entity\Setting;
use App\Core\Entity\ThemeRecord;
use App\Core\Repository\SettingRepository;
use App\Core\Repository\ThemeRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ThemeRecordManager
{
    public function __construct(
        private readonly ThemeRecordRepository $repository,
        private readonly SettingRepository $settingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function createOrUpdate(string $themeName, ?string $zipHash, ?string $marketplaceCode): ThemeRecord
    {
        $record = $this->repository->findByName($themeName);

        if ($record === null) {
            $record = new ThemeRecord();
            $record->setName($themeName);
            $record->setMarketplaceCode($marketplaceCode);
        }

        $record->setZipHash($zipHash);

        $this->repository->save($record);

        if ($record->getMarketplaceCode() !== null) {
            $context = "theme:$themeName";
            $existing = $this->settingRepository->findOneBy(['name' => 'license_key', 'context' => $context]);
            if ($existing === null) {
                $setting = new Setting();
                $setting->setName('license_key');
                $setting->setContext($context);
                $setting->setType('license_key');
                $setting->setValue(null);
                $setting->setHierarchy(0);
                $this->entityManager->persist($setting);
                $this->entityManager->flush();

                $this->logger->info('Created license_key setting for theme', ['theme' => $themeName]);
            }
        }

        return $record;
    }

    public function remove(string $themeName): void
    {
        $record = $this->repository->findByName($themeName);
        if ($record !== null) {
            $this->repository->remove($record);
        }

        $context = "theme:$themeName";
        $setting = $this->settingRepository->findOneBy(['name' => 'license_key', 'context' => $context]);
        if ($setting !== null) {
            $this->entityManager->remove($setting);
            $this->entityManager->flush();
        }
    }
}
