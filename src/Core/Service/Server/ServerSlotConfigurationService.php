<?php

namespace App\Core\Service\Server;

class ServerSlotConfigurationService
{
    public function isSlotVariable(array $variable): bool
    {
        return !empty($variable['slot_variable']) && 
               ($variable['slot_variable'] === true || 
                $variable['slot_variable'] === 'on' || 
                $variable['slot_variable'] === '1');
    }

    public function getMaxSlotsFromEggConfiguration(array $eggsConfiguration, int $eggId): ?int
    {
        if (!isset($eggsConfiguration[$eggId]['variables'])) {
            return null;
        }

        foreach ($eggsConfiguration[$eggId]['variables'] as $variable) {
            if ($this->isSlotVariable($variable) && !empty($variable['value'])) {
                return (int) $variable['value'];
            }
        }

        return null;
    }

    public function findSlotVariableInEggConfiguration(array $eggsConfiguration, int $eggId): ?array
    {
        if (!isset($eggsConfiguration[$eggId]['variables'])) {
            return null;
        }

        foreach ($eggsConfiguration[$eggId]['variables'] as $variableId => $variable) {
            if ($this->isSlotVariable($variable)) {
                return [
                    'id' => $variableId,
                    'config' => $variable
                ];
            }
        }

        return null;
    }
}
