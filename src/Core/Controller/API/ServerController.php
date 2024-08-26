<?php

namespace App\Core\Controller\API;

use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerController extends AbstractController
{
    public function __construct(
        private readonly ServerService $serverService,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/panel/api/server/{id}/details', name: 'server_details', methods: ['GET'])]
    public function serverDetails(
        ServerRepository $serverRepository,
        int $id,
    ): JsonResponse
    {
        $server = $serverRepository->find($id);
        if (empty($server)) {
            return new JsonResponse(
                ['error' => $this->translator->trans('pteroca.api.servers.not_found')],
                Response::HTTP_NOT_FOUND
            );
        }

        $serverDetails = $this->serverService->getServerDetails($server);
        return new JsonResponse($serverDetails);
    }
}