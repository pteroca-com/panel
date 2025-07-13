<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
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
        private readonly PterodactylService $pterodactylService,
    ) {}

    #[Route('/panel/api/server/{id}/allocation/create', name: 'server_allocation_create', methods: ['POST'])]
    public function createAllocation(int $id): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::ALLOCATION_CREATE);
        $response = new JsonResponse();

        $createAllocationResult = $this->serverNetworkService->createAllocation(
            $server,
            $this->getUser(),
        );

        if (!$createAllocationResult->success) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'error' => $createAllocationResult->error,
            ]));
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/allocation/{allocationId}/primary', name: 'server_allocation_make_primary', methods: ['POST'])]
    public function makePrimaryAllocation(
        int $id,
        int $allocationId,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::ALLOCATION_UPDATE);
        $response = new JsonResponse();

        $makePrimaryAllocationResult = $this->serverNetworkService->makePrimaryAllocation(
            $server,
            $this->getUser(),
            $allocationId,
        );

        if (!$makePrimaryAllocationResult->success) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'error' => $makePrimaryAllocationResult->error,
            ]));
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/allocation/{allocationId}/edit', name: 'server_allocation_edit', methods: ['POST'])]
    public function editAllocation(
        int $id,
        int $allocationId,
        Request $request,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::ALLOCATION_UPDATE);
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
        $server = $this->getServer($id, ServerPermissionEnum::ALLOCATION_DELETE);
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
