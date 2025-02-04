<?php

namespace App\Core\DTO\Collection;

use App\Core\DTO\ServerVariableDTO;

class ServerVariableCollection
{
    public function __construct(
        private readonly array $variables,
    )
    {
    }

    public function getByEnvVariable(string $envVariable): ?ServerVariableDTO
    {
        $foundVariable = array_filter($this->variables, function (ServerVariableDTO $variable) use ($envVariable) {
            return $variable->envVariable === $envVariable;
        });
        $foundVariable = current($foundVariable);

        return $foundVariable ?: null;
    }

    public function all(): array
    {
        return $this->variables;
    }
}
