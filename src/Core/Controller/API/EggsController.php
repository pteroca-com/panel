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
