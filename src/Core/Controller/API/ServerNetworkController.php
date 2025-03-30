<?php

namespace App\Core\Controller\API;

use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerDatabaseService;
use App\Core\Service\Server\ServerNetworkService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ServerNetworkController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerDatabaseService $serverDatabaseService,
        private readonly ServerNetworkService $serverNetworkService,
    ) {}

    #[Route('/panel/api/server/{id}/allocation/{allocationId}/delete', name: 'server_allocation_delete', methods: ['DELETE'])]
    public function deleteAllocation(
        int $id,
        int $allocationId,
    ): JsonResponse
    {
        $server = $this->getServer($id);
        $response = new JsonResponse();

        $deleteAllocationResult = $this->serverNetworkService->deleteAllocation(
            $server,
            $this->getUser(),
            $allocationId,
        );

        if (!$deleteAllocationResult->success) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'error' => $deleteAllocationResult->error,
            ]));
        }

        return $response;
    }
}
