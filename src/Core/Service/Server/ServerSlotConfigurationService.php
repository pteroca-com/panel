<?php

namespace App\Core\Service\Server;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ServerSlotConfigurationService
{
    public static function isSlotVariable(array $variable): bool
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

    public static function validateSlotVariablesConfiguration(
        array $slotPrices, 
        ?string $eggsConfigurationJson, 
        ExecutionContextInterface $context
    ): void {
        if (count($slotPrices) > 0) {
            $eggsConfiguration = json_decode($eggsConfigurationJson ?? '{}', true);
            
            if (!empty($eggsConfiguration)) {
                foreach ($eggsConfiguration as $eggConfig) {
                    $hasSlotVariable = false;
                    
                    if (isset($eggConfig['variables'])) {
                        foreach ($eggConfig['variables'] as $variable) {
                            if (self::isSlotVariable($variable)) {
                                $hasSlotVariable = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$hasSlotVariable) {
                        $context->buildViolation('pteroca.crud.product.slot_variable_required_for_slot_prices')
                            ->setTranslationDomain('messages')
                            ->atPath('eggsConfiguration')
                            ->addViolation();
                        break;
                    }
                }
            }
        }
    }
}
