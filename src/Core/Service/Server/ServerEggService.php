<?php

namespace App\Core\Service\Server;

use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerEggService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function prepareEggsConfiguration(int $pterodactylServerId): string
    {
        $pterodactylServer = $this->pterodactylService
            ->getApi()
            ->servers
            ->get($pterodactylServerId, ['include' => 'variables'])
            ->toArray();

        $pterodactylServerVariables = $pterodactylServer['relationships']['variables']->toArray();
        $preparedVariables = [];
        foreach ($pterodactylServerVariables as $variable) {
            $preparedVariables[$variable['attributes']['id']] = [
                'value' => $variable['attributes']['default_value'],
                'user_viewable' => $variable['attributes']['user_viewable'],
                'user_editable' => $variable['attributes']['user_editable'],
            ];
        }

        $serverEggsConfiguration = [
            $pterodactylServer['egg'] => [
                'options' => [
                    'startup' => [
                        'value' => $pterodactylServer['container']['startup_command'],
                    ],
                    'docker_image' => [
                        'value' => $pterodactylServer['container']['image'],
                    ],
                ],
                'variables' => $preparedVariables,
            ]
        ];

        return json_encode($serverEggsConfiguration);
    }

    public function prepareEggsDataByNest(int $nestId): array
    {
        $eggs = $this->pterodactylService
            ->getApi()
            ->nest_eggs
            ->all($nestId, ['include' => 'variables'])
            ->toArray();

        $translations = $this->getEggsTranslations();
        $choices = [];
        $loadedEggs = [];

        foreach ($eggs as $egg) {
            $choices[$egg->name] = $egg->id;
            $loadedEggs[$egg->id] = $egg;
        }

        return [
            'choices' => $choices,
            'eggs' => $loadedEggs,
            'translations' => $translations,
        ];
    }

    private function getEggsTranslations(): array
    {
        return [
            'egg_information' => $this->translator->trans('pteroca.crud.product.egg_information'),
            'alert' => $this->translator->trans('pteroca.crud.product.egg_options_you_can_edit'),
            'variables' => $this->translator->trans('pteroca.crud.product.egg_variables'),
            'configuration' => $this->translator->trans('pteroca.crud.product.egg_configuration'),
            'default_configuration' => $this->translator->trans('pteroca.crud.product.egg_default_configuration'),
            'egg_variable_name' => $this->translator->trans('pteroca.crud.product.egg_variable_name'),
            'egg_variable_description' => $this->translator->trans('pteroca.crud.product.egg_variable_description'),
            'egg_variable_value' => $this->translator->trans('pteroca.crud.product.egg_variable_value'),
            'egg_variable_user_viewable' => $this->translator->trans('pteroca.crud.product.egg_variable_user_viewable'),
            'egg_variable_user_editable' => $this->translator->trans('pteroca.crud.product.egg_variable_user_editable'),
            'egg_variable_slot_variable' => $this->translator->trans('pteroca.crud.product.egg_variable_slot_variable'),
            'egg_variable_slot_variable_hint' => $this->translator->trans('pteroca.crud.product.egg_variable_slot_variable_hint'),
            'slot_variable_not_configured_egg' => $this->translator->trans('pteroca.crud.product.slot_variable_not_configured_egg'),
            'slot_variables_unconfigured_eggs' => $this->translator->trans('pteroca.crud.product.slot_variables_unconfigured_eggs'),
        ];
    }
}
