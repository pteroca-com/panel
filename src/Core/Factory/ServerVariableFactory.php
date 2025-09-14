<?php

namespace App\Core\Factory;

use App\Core\DTO\Pterodactyl\Resource;
use App\Core\DTO\ServerVariableDTO;

class ServerVariableFactory
{
    /**
     * @return array<Resource>
     */
    public function createFromCollection(array $variables): array
    {
        $preparedVariables = [];

        foreach ($variables as $variable) {
            $variableRules = explode('|', $variable['rules']);

            $preparedVariables[] = new ServerVariableDTO(
                $variable['name'],
                $variable['description'],
                $variable['env_variable'],
                $variable['default_value'],
                $variable['server_value'],
                $variable['user_editable'],
                $variable['user_viewable'],
                $variableRules,
            );
        }

        return $preparedVariables;
    }
}
