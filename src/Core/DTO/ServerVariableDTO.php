<?php

namespace App\Core\DTO;

readonly class ServerVariableDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public string $envVariable,
        public string $defaultValue,
        public string $serverValue,
        public bool   $isUserEditable,
        public bool   $isUserViewable,
        public array  $rules,
    )
    {
    }
}
