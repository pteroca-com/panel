<?php

namespace App\Core\DTO;

class ServerVariableDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $envVariable,
        public readonly string $defaultValue,
        public readonly string $serverValue,
        public readonly bool $isUserEditable,
        public readonly bool $isUserViewable,
        public readonly array $rules,
    )
    {
    }
}
