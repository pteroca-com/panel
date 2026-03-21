<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ServerUserVariableService
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * Extracts and validates user-provided variable values against the product's
     * eggsConfiguration rules. Returns only variables marked as user_required,
     * keyed by env_variable name.
     *
     * @throws \Exception if a required variable is missing or fails validation
     */
    public function extractAndValidate(array $submittedVars, Product $product, int $eggId): array
    {
        try {
            $eggsConfiguration = json_decode($product->getEggsConfiguration() ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $eggVariables = $eggsConfiguration[$eggId]['variables'] ?? [];

        foreach ($eggVariables as $varConfig) {
            if (empty($varConfig['user_required'])) {
                continue;
            }

            $envVariable = $varConfig['env_variable'] ?? '';
            if (empty($envVariable)) {
                continue;
            }

            $value = trim($submittedVars[$envVariable] ?? '');
            $name = $varConfig['name'] ?? $envVariable;

            if ($value === '') {
                throw new \Exception($this->translator->trans(
                    'pteroca.store.user_required_variable_missing',
                    ['%name%' => $name]
                ));
            }

            $rules = $varConfig['rules'] ?? '';
            if (!empty($rules) && !$this->validateRules($value, $rules)) {
                throw new \Exception($this->translator->trans(
                    'pteroca.store.user_required_variable_invalid',
                    ['%name%' => $name]
                ));
            }
        }

        $result = [];
        foreach ($eggVariables as $varConfig) {
            if (empty($varConfig['user_required'])) {
                continue;
            }
            $envVariable = $varConfig['env_variable'] ?? '';
            if (empty($envVariable)) {
                continue;
            }
            $value = trim($submittedVars[$envVariable] ?? '');
            if ($value !== '') {
                $result[$envVariable] = $value;
            }
        }

        return $result;
    }

    private function validateRules(string $value, string $rules): bool
    {
        $ruleList = explode('|', $rules);
        $isNumeric = in_array('numeric', $ruleList, true) || in_array('integer', $ruleList, true);
        $constraints = [];

        foreach ($ruleList as $rule) {
            [$ruleName, $ruleParam] = str_contains($rule, ':')
                ? explode(':', $rule, 2)
                : [$rule, null];

            switch ($ruleName) {
                case 'required':
                    $constraints[] = new Assert\NotBlank();
                    break;
                case 'string':
                    $constraints[] = new Assert\Type('string');
                    break;
                case 'numeric':
                    $constraints[] = new Assert\Type('numeric');
                    break;
                case 'integer':
                    $constraints[] = new Assert\Type('integer');
                    break;
                case 'email':
                    $constraints[] = new Assert\Email();
                    break;
                case 'url':
                    $constraints[] = new Assert\Url();
                    break;
                case 'ip':
                    $constraints[] = new Assert\Ip();
                    break;
                case 'min':
                    if ($ruleParam !== null) {
                        $constraints[] = $isNumeric
                            ? new Assert\GreaterThanOrEqual((float) $ruleParam)
                            : new Assert\Length(min: (int) $ruleParam);
                    }
                    break;
                case 'max':
                    if ($ruleParam !== null) {
                        $constraints[] = $isNumeric
                            ? new Assert\LessThanOrEqual((float) $ruleParam)
                            : new Assert\Length(max: (int) $ruleParam);
                    }
                    break;
                case 'between':
                    if ($ruleParam !== null) {
                        [$min, $max] = array_map('floatval', explode(',', $ruleParam, 2));
                        $constraints[] = $isNumeric
                            ? new Assert\Range(min: $min, max: $max)
                            : new Assert\Length(min: (int) $min, max: (int) $max);
                    }
                    break;
                case 'in':
                    if ($ruleParam !== null) {
                        $constraints[] = new Assert\Choice(explode(',', $ruleParam));
                    }
                    break;
                case 'regex':
                    if ($ruleParam !== null) {
                        $constraints[] = new Assert\Regex($ruleParam);
                    }
                    break;
            }
        }

        if (empty($constraints)) {
            return true;
        }

        $validator = Validation::createValidator();
        return count($validator->validate($value, $constraints)) === 0;
    }
}
