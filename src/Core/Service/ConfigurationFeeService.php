<?php

namespace App\Core\Service;

use App\Core\Contract\UserInterface;
use App\Core\Enum\SettingEnum;
use App\Core\Repository\ServerRepository;

class ConfigurationFeeService
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly ServerRepository $serverRepository,
    ) {}

    public function isConfigurationFeeEnabled(): bool
    {
        return (bool) $this->settingService->getSetting(SettingEnum::CONFIGURATION_FEE_ENABLED->value);
    }

    public function getConfigurationFeeAmount(): float
    {
        return (float) $this->settingService->getSetting(SettingEnum::CONFIGURATION_FEE_AMOUNT->value);
    }

    public function shouldApplyConfigurationFee(UserInterface $user): bool
    {
        if (!$this->isConfigurationFeeEnabled()) {
            return false;
        }

        return !$this->hasUserPurchasedServerBefore($user);
    }

    public function calculateTotalWithConfigurationFee(float $basePrice, UserInterface $user): float
    {
        if (!$this->shouldApplyConfigurationFee($user)) {
            return $basePrice;
        }

        return $basePrice + $this->getConfigurationFeeAmount();
    }

    private function hasUserPurchasedServerBefore(UserInterface $user): bool
    {
        $serverCount = $this->serverRepository->count(['user' => $user]);
        return $serverCount > 0;
    }
}
