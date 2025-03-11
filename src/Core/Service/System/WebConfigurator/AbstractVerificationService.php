<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;

abstract class AbstractVerificationService
{
    protected const REQUIRED_FIELDS = [];

    protected function validateRequiredFields(array $data): bool
    {
        return count(array_intersect_key(array_flip(self::REQUIRED_FIELDS), $data)) === count(self::REQUIRED_FIELDS);
    }

    abstract public function validateConnection(array $data): ConfiguratorVerificationResult;
}