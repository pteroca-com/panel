<?php

namespace App\Core\Controller\API;

use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class EggsController extends AbstractController
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
    ) {}

    #[Route('/panel/api/get-eggs/{nestId}', name: 'get_eggs', methods: ['GET'])]
    public function getEggs(int $nestId): JsonResponse
    {
        $eggs = $this->pterodactylService->getApi()->nest_eggs->all($nestId)->toArray();
        $choices = [];

        foreach ($eggs as $egg) {
            $choices[$egg->name] = $egg->id;
        }

        return new JsonResponse($choices);
    }
}