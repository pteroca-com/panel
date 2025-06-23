<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerBackupService;
use App\Core\Trait\InternalServerApiTrait;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ServerBackupController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerBackupService $serverBackupService,
        private readonly PterodactylService $pterodactylService,
    ) {}

    #[Route('/panel/api/server/{id}/backup/create', name: 'server_backup_create', methods: ['POST'])]
    public function createBackup(
        int $id,
        Request $request,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::BACKUP_CREATE);
        $response = new JsonResponse();

        try {
            $createdBackup = $this->serverBackupService->createBackup(
                $server,
                $this->getUser(),
                $request->request->all('Backup')['name'] ?? null,
                $request->request->all('Backup')['ignoredFiles'] ?? '',
            );
            $response->setData($createdBackup);
        } catch (TooManyRequestsHttpException) {
            $response->setStatusCode(429);
        } catch (Exception $exception) {
            $errorData = json_decode($exception->getMessage(), true);
            $error = current($errorData['errors'] ?? []);
            $response->setStatusCode($error['status'] ?? 400);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/backup/{backupId}/download', name: 'server_backup_download', methods: ['GET'])]
    public function downloadBackup(
        int $id,
        string $backupId,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::BACKUP_DOWNLOAD);
        $response = new JsonResponse();

        try {
            $downloadUrl = $this->serverBackupService->getBackupDownloadUrl(
                $server,
                $this->getUser(),
                $backupId,
            );
            $response->setData(['url' => $downloadUrl]);
        } catch (Exception $exception) {
            $response->setStatusCode(400);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/backup/{backupId}/delete', name: 'server_backup_delete', methods: ['DELETE'])]
    public function backupLockToggle(
        int $id,
        string $backupId,
    ): Response
    {
        $server = $this->getServer($id, ServerPermissionEnum::BACKUP_DELETE);
        $response = new Response();

        try {
            $this->serverBackupService->deleteBackup(
                $server,
                $this->getUser(),
                $backupId,
            );
            $response->setStatusCode(204);
        } catch (Exception $exception) {
            $response->setStatusCode(400);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/backup/{backupId}/restore', name: 'server_backup_restore', methods: ['POST'])]
    public function restoreBackup(
        int $id,
        string $backupId,
        Request $request,
    ): Response
    {
        $server = $this->getServer($id, ServerPermissionEnum::BACKUP_RESTORE);
        $response = new Response();

        try {
            $truncate = $request->request->getBoolean('truncate', false);
            
            $this->serverBackupService->restoreBackup(
                $server,
                $this->getUser(),
                $backupId,
                $truncate,
            );
            $response->setStatusCode(204);
        } catch (Exception $exception) {
            $response->setStatusCode(400);
        }

        return $response;
    }
}
