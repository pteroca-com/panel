<?php

namespace App\Core\Exception\Pterodactyl;

class PterodactylValidationException extends PterodactylApiException
{
    private array $validationErrors = [];

    public function setValidationErrors(array $errors): void
    {
        $this->validationErrors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
