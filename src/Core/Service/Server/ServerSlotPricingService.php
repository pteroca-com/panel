<?php

namespace App\Core\Service\Server;

use App\Core\Contract\ProductPriceInterface;
use App\Core\Entity\Server;
use App\Core\Service\Pterodactyl\PterodactylService;
use JsonException;
use RuntimeException;

class ServerSlotPricingService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
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

            $slotVariableId = null;
            foreach ($eggsConfiguration[$eggId]['variables'] as $variableId => $variable) {
                if (isset($variable['slot_variable']) && $variable['slot_variable'] === 'on') {
                    $slotVariableId = $variableId;
                    break;
                }
            }

            if (!$slotVariableId) {
                $this->throwSlotException('No slot variable found in eggs configuration');
            }

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
        if ($price->getType()->value === 'slot' && $slots !== null && $slots > 0) {
            return $price->getPrice() * $slots;
        }
        
        return $price->getPrice();
    }

    public function hasSlotPricing(Server $server): bool
    {
        foreach ($server->getServerProduct()->getPrices() as $price) {
            if ($price->getType()->value === 'slot') {
                return true;
            }
        }
        
        return false;
    }

    public function hasActiveSlotPricing(Server $server): bool
    {
        return $server->getServerProduct()->getSelectedPrice()->getType()->value === 'slot';
    }

    private function throwSlotException(string $message): never
    {
        throw new RuntimeException('Server slot pricing error: ' . $message);
    }
}
