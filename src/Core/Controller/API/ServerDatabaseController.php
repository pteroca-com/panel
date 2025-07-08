<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerDatabaseService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerDatabaseController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerDatabaseService $serverDatabaseService,
        private readonly PterodactylService $pterodactylService,
    ) {}

    #[Route('/panel/api/server/{id}/database/all', name: 'server_database_get_all', methods: ['GET'])]
    public function getAllDatabases(
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::DATABASE_READ);
        $response = new JsonResponse();

        try {
            $pterodactylDatabases = $this->serverDatabaseService->getAllDatabases(
                $server,
                $this->getUser(),
            );
            $response->setData($pterodactylDatabases);
        } catch (\Exception) {
            $response->setStatusCode(400);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/database/create', name: 'server_database_create', methods: ['POST'])]
    public function createDatabase(
        int $id,
        Request $request,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::DATABASE_CREATE);
        $response = new JsonResponse();
        $payload = $request->request->all('Database');

        try {
            $this->serverDatabaseService->createDatabase(
                $server,
                $this->getUser(),
                $payload['name'],
                $payload['connections_from'],
            );
        } catch (\Exception $e) {
            // TODO log error
            $response->setStatusCode(400);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/database/{databaseId}/delete', name: 'server_database_delete', methods: ['DELETE'])]
    public function deleteDatabase(
        int $id,
        int $databaseId,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::DATABASE_DELETE);
        $response = new JsonResponse();

        try {
            $this->serverDatabaseService->deleteDatabase(
                $server,
                $this->getUser(),
                $databaseId,
            );
        } catch (\Exception) {
            $response->setStatusCode(400);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/database/{databaseId}/rotate-password', name: 'server_database_rotate_password', methods: ['POST'])]
    public function rotatePassword(
        int $id,
        string $databaseId,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::DATABASE_UPDATE);
        $response = new JsonResponse();

        try {
            $changedDatabaseData = $this->serverDatabaseService->rotatePassword(
                $server,
                $this->getUser(),
                $databaseId,
            );
            $response->setData($changedDatabaseData);
        } catch (\Exception $e) {
            // TODO set error message
            $response->setStatusCode(400);
        }

        return $response;
    }
}
