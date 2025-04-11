<?php

namespace App\Core\Controller\API;

use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerNetworkService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerNetworkController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerNetworkService $serverNetworkService,
    ) {}

    #[Route('/panel/api/server/{id}/allocation/{allocationId}/edit', name: 'server_allocation_edit', methods: ['POST'])]
    public function editAllocation(
        int $id,
        int $allocationId,
        Request $request,
    ): JsonResponse
    {
        $server = $this->getServer($id);
        $response = new JsonResponse();

        $editAllocationResult = $this->serverNetworkService->editAllocation(
            $server,
            $this->getUser(),
            $allocationId,
            $request->toArray()['notes'] ?? '',
        );

        if (!$editAllocationResult->success) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'error' => $editAllocationResult->error,
            ]));
        }

        return $response;
    }

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
