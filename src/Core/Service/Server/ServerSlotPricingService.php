<?php

namespace App\Core\Service\Server;

use App\Core\Contract\ProductPriceInterface;
use App\Core\Contract\ProductInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Service\Product\ProductPriceCalculatorService;
use App\Core\Service\Pterodactyl\PterodactylService;
use JsonException;
use RuntimeException;

class ServerSlotPricingService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ProductPriceCalculatorService $productPriceCalculatorService,
        private readonly ServerSlotConfigurationService $serverSlotConfigurationService,
    ) {}

    public function getServerSlots(Server $server): int
    {
        try {
            $eggsConfigurationJson = $server->getServerProduct()->getEggsConfiguration();
            if (empty($eggsConfigurationJson)) {
                $this->throwSlotException('Missing eggs configuration for server');
            }

            $eggsConfiguration = json_decode($eggsConfigurationJson, true, 512, JSON_THROW_ON_ERROR);
            
            $pterodactylServer = $this->pterodactylService->getApi()
                ->servers
                ->get($server->getPterodactylServerId(), ['include' => 'variables']);

            $eggId = $pterodactylServer->get('egg');
            $serverVariables = $pterodactylServer->get('relationships')['variables']?->toArray() ?? [];

            if (!isset($eggsConfiguration[$eggId]['variables']) || empty($serverVariables)) {
                $this->throwSlotException('Missing egg variables configuration or server variables');
            }

            $slotVariable = $this->serverSlotConfigurationService->findSlotVariableInEggConfiguration($eggsConfiguration, $eggId);
            if (!$slotVariable) {
                $this->throwSlotException('No slot variable found in eggs configuration');
            }
            $slotVariableId = $slotVariable['id'];

            foreach ($serverVariables as $serverVariable) {
                if (isset($serverVariable['attributes']['id']) && $serverVariable['attributes']['id'] == $slotVariableId) {
                    return (int) $serverVariable['attributes']['server_value'];
                }
            }

            $this->throwSlotException('Slot variable not found in server variables');
        } catch (JsonException|\Exception $e) {
            $this->throwSlotException('Failed to retrieve server slots: ' . $e->getMessage());
        }
    }

    public function calculateSlotPrice(ProductPriceInterface $price, ?int $slots = null): float
    {
        return $this->productPriceCalculatorService->calculateFinalPrice($price, $slots);
    }

    public function hasSlotPricing(Server $server): bool
    {
        foreach ($server->getServerProduct()->getPrices() as $price) {
            if ($price->getType()->value === ProductPriceTypeEnum::SLOT->value) {
                return true;
            }
        }
        
        return false;
    }

    public function hasActiveSlotPricing(Server $server): bool
    {
        return $server->getServerProduct()->getSelectedPrice()->getType()->value === ProductPriceTypeEnum::SLOT->value;
    }

    public function hasSlotPrices(ProductInterface $product): bool
    {
        foreach ($product->getPrices() as $price) {
            if ($price->getType()->value === ProductPriceTypeEnum::SLOT->value) {
                return true;
            }
        }
        
        return false;
    }

    private function throwSlotException(string $message): never
    {
        throw new RuntimeException('Server slot pricing error: ' . $message);
    }
}
