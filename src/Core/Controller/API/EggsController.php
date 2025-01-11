<?php

namespace App\Core\Controller\API;

use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class EggsController extends APIAbstractController
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/panel/api/get-eggs/{nestId}', name: 'get_eggs', methods: ['GET'])]
    public function getEggs(int $nestId): JsonResponse
    {
        $this->requireAdminRoleForAPIEndpoint();

        $eggs = $this->pterodactylService
            ->getApi()
            ->nest_eggs
            ->all($nestId, ['include' => 'variables'])
            ->toArray();

        $choices = [];
        $loadedEggs = [];
        $translations = [
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
        ];

        foreach ($eggs as $egg) {
            $choices[$egg->name] = $egg->id;
            $loadedEggs[$egg->id] = $egg;
        }

        return new JsonResponse([
            'choices' => $choices,
            'eggs' => $loadedEggs,
            'translations' => $translations,
        ]);
    }
}
