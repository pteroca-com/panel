<?php

namespace App\Core\Trait;

use App\Core\Service\Server\ServerSlotConfigurationService;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SlotVariableValidationTrait
{
    protected function validateSlotVariablesConfiguration(ExecutionContextInterface $context): void
    {
        if (count($this->getSlotPrices()) > 0) {
            $eggsConfiguration = json_decode($this->getEggsConfiguration() ?? '{}', true);
            
            if (!empty($eggsConfiguration)) {
                $serverSlotConfigurationService = $this->serverSlotConfigurationService ?? new ServerSlotConfigurationService();

                foreach ($eggsConfiguration as $eggConfig) {
                    $hasSlotVariable = false;
                    
                    if (isset($eggConfig['variables'])) {
                        foreach ($eggConfig['variables'] as $variable) {
                            if ($serverSlotConfigurationService->isSlotVariable($variable)) {
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
