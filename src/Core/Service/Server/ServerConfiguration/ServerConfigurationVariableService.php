<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\UserRoleEnum;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class ServerConfigurationVariableService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly PterodactylService $pterodactylService,
    ) {
        parent::__construct($this->pterodactylService);
    }

    public function updateServerVariable(
        Server $server,
        UserInterface $user,
        string $variableKey,
        string $variableValue,
    ): void
    {
        $serverDetails = $this->getServerDetails($server, ['variables']);
        $serverVariable = $this->getServerVariable($serverDetails, $variableKey);
        $this->validateVariable($server, $serverDetails, $serverVariable, $variableValue, $user);

        $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->put("servers/{$server->getPterodactylServerIdentifier()}/startup/variable", [], [
                'key' => $variableKey,
                'value' => $variableValue,
            ]);
    }

    private function getServerVariable(array $serverDetails, string $variableKey): array
    {
        $serverVariables = $serverDetails['relationships']['variables']->toArray();
        $foundVariable = current(array_filter($serverVariables, function ($variable) use ($variableKey) {
            return $variable['attributes']['env_variable'] === $variableKey;
        }));

        if (empty($foundVariable['attributes'])) {
            throw new \Exception('Variable not found');
        }

        return $foundVariable['attributes'];
    }

    private function isVariableEditableForUser(Server $server, array $serverDetails, array $serverVariable, UserInterface $user): bool
    {
        if (in_array(UserRoleEnum::ROLE_ADMIN->value, $user->getRoles())) {
            return true;
        }

        if ($serverVariable['user_editable'] === false) {
            return false;
        }

        $productEggConfiguration = json_decode($server->getServerProduct()->getEggsConfiguration() ?? '');
        if (empty($productEggConfiguration)) {
            return false;
        }

        $currentServerEgg = $serverDetails['egg'] ?? null;
        $variableProductEggConfiguration = $productEggConfiguration->$currentServerEgg
            ?->variables
            ?->{$serverVariable['id']} ?? null;
        if (empty($variableProductEggConfiguration)) {
            return false;
        }

        return $variableProductEggConfiguration->user_editable ?? false;
    }

    private function validateVariable(Server $server, array $serverDetails, array $serverVariable, string $variableValue, UserInterface $user): void
    {
        if (!$this->isVariableEditableForUser($server, $serverDetails, $serverVariable, $user)) {
            throw new \Exception('Variable is not editable for user');
        }

        $variableValueRules = $serverVariable['rules'];
        $rules = explode('|', $variableValueRules);
        
        if (in_array('boolean', $rules)) {
            if (!in_array($variableValue, ['0', '1', 'true', 'false', ''])) {
                throw new \Exception('Variable value is invalid');
            }
            return;
        }

        $ruleMap = [
            'required' => Assert\NotBlank::class,
            'string' => Assert\Type::class,
            'numeric' => Assert\Type::class,
            'email' => Assert\Email::class,
            'url' => Assert\Url::class,
            'ip' => Assert\Ip::class,
            'regex' => Assert\Regex::class,
            'length' => Assert\Length::class,
            'range' => Assert\Range::class,
        ];

        $constraints = [];

        foreach ($rules as $rule) {
            if (str_contains($rule, ':')) {
                $partedRules = explode(':', $rule);
                $rule = $partedRules[0];
            }

            if (isset($ruleMap[$rule])) {
                $constraints[] = match ($rule) {
                    'string', 'numeric' => new $ruleMap[$rule](['type' => $rule]),
                    'regex' => new $ruleMap[$rule](['pattern' => $partedRules[1] ?? '']),
                    default => new $ruleMap[$rule]([]),
                };
            }
        }

        if (!empty($constraints)) {
            $validator = Validation::createValidator();
            $violations = $validator->validate($variableValue, $constraints);

            if (count($violations) > 0) {
                throw new \Exception('Variable value is invalid');
            }
        }
    }
}
