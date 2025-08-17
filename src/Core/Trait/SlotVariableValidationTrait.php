<?php

namespace App\Core\Trait;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SlotVariableValidationTrait
{
    protected function validateSlotVariablesConfiguration(ExecutionContextInterface $context): void
    {
        if (count($this->getSlotPrices()) > 0) {
            $eggsConfiguration = json_decode($this->getEggsConfiguration() ?? '{}', true);
            
            if (!empty($eggsConfiguration)) {
                foreach ($eggsConfiguration as $eggConfig) {
                    $hasSlotVariable = false;
                    
                    if (isset($eggConfig['variables'])) {
                        foreach ($eggConfig['variables'] as $variable) {
                            if (isset($variable['slot_variable']) && $variable['slot_variable'] === true) {
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
