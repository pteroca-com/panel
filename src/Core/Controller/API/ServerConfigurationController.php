<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerLogActionEnum;
use App\Core\Enum\ServerPermissionEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerConfiguration\ServerAutoRenewalService;
use App\Core\Service\Server\ServerConfiguration\ServerConfigurationDetailsService;
use App\Core\Service\Server\ServerConfiguration\ServerConfigurationOptionService;
use App\Core\Service\Server\ServerConfiguration\ServerConfigurationVariableService;
use App\Core\Service\Server\ServerConfiguration\ServerReinstallationService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerConfigurationController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerLogService $serverLogService,
        private readonly PterodactylService $pterodactylService,
    ) {}

    #[Route('/panel/api/server/{id}/startup/variable', name: 'server_startup_variable_update', methods: ['POST'])]
    public function updateServerVariable(
        Request $request,
        ServerConfigurationVariableService $serverConfigurationVariableService,
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::STARTUP_UPDATE);
        $variableData = $this->getValidatedRequestData($request);
        
        $serverConfigurationVariableService->updateServerVariable(
            $server,
            $this->getUser(),
            $variableData['key'],
            $variableData['value'],
        );

        $this->serverLogService->logServerAction(
            $this->getUser(),
            $server,
            ServerLogActionEnum::CHANGE_STARTUP_VARIABLE,
            $variableData,
        );

        return new JsonResponse();
    }

    #[Route('/panel/api/server/{id}/startup/option', name: 'server_startup_option_update', methods: ['POST'])]
    public function updateStartupVariable(
        Request $request,
        ServerConfigurationOptionService $serverConfigurationOptionService,
        int $id,
    ): JsonResponse
    {
        $variableData = $this->getValidatedRequestData($request);
        $permission = ($variableData['key'] === 'docker_image') 
            ? ServerPermissionEnum::STARTUP_DOCKER_IMAGE 
            : ServerPermissionEnum::STARTUP_UPDATE;
        $server = $this->getServer($id, $permission);
        
        $serverConfigurationOptionService->updateServerStartupOption(
            $server,
            $this->getUser(),
            $variableData['key'],
            $variableData['value'],
        );

        $this->serverLogService->logServerAction(
            $this->getUser(),
            $server,
            ServerLogActionEnum::CHANGE_STARTUP_OPTION,
            $variableData,
        );

        return new JsonResponse();
    }

    #[Route('/panel/api/server/{id}/details/update', name: 'server_details_update', methods: ['POST'])]
    public function updateServerDetails(
        Request $request,
        ServerConfigurationDetailsService $serverConfigurationDetailsService,
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SETTINGS_RENAME);
        $variableData = $this->getValidatedRequestData($request);
        
        $serverConfigurationDetailsService->updateServerDetails(
            $server,
            $this->getUser(),
            $variableData['key'],
            $variableData['value'] ?? null,
        );

        $serverConfigurationDetailsService->updateServerEntityName(
            $server, 
            $variableData['key'],
        );

        $this->serverLogService->logServerAction(
            $this->getUser(),
            $server,
            ServerLogActionEnum::CHANGE_DETAILS,
            $variableData,
        );

        return new JsonResponse();
    }

    #[Route('/panel/api/server/{id}/reinstall', name: 'server_reinstall', methods: ['POST'])]
    public function reinstallServer(
        Request $request,
        ServerReinstallationService $serverReinstallationService,
        int $id,
    ): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SETTINGS_REINSTALL);
        $variableData = $this->getValidatedRequestData($request);
        
        $serverReinstallationService->reinstallServer($server, $this->getUser(), $variableData['key']);

        $this->serverLogService->logServerAction(
            $this->getUser(),
            $server,
            ServerLogActionEnum::REINSTALL,
            $variableData,
        );

        return new JsonResponse();
    }

    #[Route('/panel/api/server/{id}/auto-renewal/toggle', name: 'server_auto_renewal_toggle', methods: ['POST'])]
    public function toggleAutoRenewal(
        Request $request,
        ServerAutoRenewalService $serverAutoRenewalService,
        int $id,
    ): JsonResponse
    {
        $server = $this->serverRepository->find($id);
        if (empty($server) || ($server->getUser() !== $this->getUser() && !$this->isGranted(UserRoleEnum::ROLE_ADMIN->name))) {
            throw $this->createAccessDeniedException();
        }
        
        $requestData = $request->toArray();
        $serverAutoRenewalService->toggleAutoRenewal($server, $requestData['value']);

        $this->serverLogService->logServerAction(
            $this->getUser(),
            $server,
            ServerLogActionEnum::TOGGLE_AUTO_RENEWAL,
            $requestData,
        );

        return new JsonResponse();
    }

    private function getValidatedRequestData(Request $request): array
    {
        $variableData = $request->toArray();
        
        if (!isset($variableData['key'])) {
            throw new \Exception('Invalid data');
        }

        return $variableData;
    }
}
