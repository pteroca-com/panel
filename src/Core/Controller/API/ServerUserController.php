<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerUserService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerUserController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerUserService $serverUserService,
        private readonly PterodactylService $pterodactylService,
    ) {}

    #[Route('/panel/api/server/{id}/users/all', name: 'server_users_get_all', methods: ['GET'])]
    public function getAllUsers(int $id): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::USER_READ);
        $response = new JsonResponse();

        try {
            $subusers = $this->serverUserService->getAllSubusers($server, $this->getUser());
            $response->setData($subusers);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/users/create', name: 'server_users_create', methods: ['POST'])]
    public function createUser(int $id, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::USER_CREATE);
        $response = new JsonResponse();
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || empty($data['email'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Email is required']);
            return $response;
        }

        if (!isset($data['permissions']) || !is_array($data['permissions']) || empty($data['permissions'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'At least one permission is required']);
            return $response;
        }

        try {
            $result = $this->serverUserService->addExistingUserToServer(
                $server,
                $this->getUser(),
                $data['email'],
                $data['permissions'] ?? []
            );
            $response->setData($result);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/users/{userUuid}', name: 'server_users_get', methods: ['GET'])]
    public function getSubuser(int $id, string $userUuid): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::USER_READ);
        $response = new JsonResponse();

        try {
            $subuser = $this->serverUserService->getSubuser($server, $this->getUser(), $userUuid);
            $response->setData($subuser);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/users/{userUuid}/permissions', name: 'server_users_update_permissions', methods: ['POST'])]
    public function updateUserPermissions(int $id, string $userUuid, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::USER_UPDATE);
        $response = new JsonResponse();
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Permissions array is required']);
            return $response;
        }

        if (empty($data['permissions'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'At least one permission is required']);
            return $response;
        }

        if (!isset($data['email']) || empty($data['email'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Email is required']);
            return $response;
        }

        try {
            $result = $this->serverUserService->updateSubuserPermissions(
                $server,
                $this->getUser(),
                $userUuid,
                $data['email'],
                $data['permissions']
            );
            $response->setData($result);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/users/{userUuid}/delete', name: 'server_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, string $userUuid, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::USER_DELETE);
        $response = new JsonResponse();

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || empty($data['email'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Email is required']);
            return $response;
        }

        try {
            $this->serverUserService->deleteSubuser($server, $this->getUser(), $userUuid, $data['email']);
            $response->setData(['success' => true]);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }
}
