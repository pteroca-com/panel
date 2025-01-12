<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerLogActionEnum;
use App\Core\Enum\UserRoleEnum;
use App\Core\Exception\NotAllowedInDemoModeException;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Server\ServerConfiguration\ServerConfigurationDetailsService;
use App\Core\Service\Server\ServerConfiguration\ServerConfigurationOptionService;
use App\Core\Service\Server\ServerConfiguration\ServerConfigurationVariableService;
use App\Core\Service\Server\ServerConfiguration\ServerReinstallationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerConfigurationController extends APIAbstractController
{
    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerLogService $serverLogService,
    ) {}

    #[Route('/panel/api/server/{id}/startup/variable', name: 'server_startup_variable_update', methods: ['POST'])]
    public function updateServerVariable(
        Request $request,
        ServerConfigurationVariableService $serverConfigurationVariableService,
        int $id,
    ): JsonResponse
    {
        throw new NotAllowedInDemoModeException();

        [$server, $variableData] = $this->extractValidatedServerVariableData($request, $id);
        $serverConfigurationVariableService->updateServerVariable(
            $server,
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
        throw new NotAllowedInDemoModeException();

        [$server, $variableData] = $this->extractValidatedServerVariableData($request, $id);
        $serverConfigurationOptionService->updateServerStartupOption(
            $server,
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
        throw new NotAllowedInDemoModeException();

        [$server, $variableData] = $this->extractValidatedServerVariableData($request, $id);
        $serverConfigurationDetailsService->updateServerDetails(
            $server,
            $variableData['key'],
            $variableData['value'] ?? null,
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
        throw new NotAllowedInDemoModeException();

        [$server, $variableData] = $this->extractValidatedServerVariableData($request, $id);
        $serverReinstallationService->reinstallServer($server, $variableData['key']);

        $this->serverLogService->logServerAction(
            $this->getUser(),
            $server,
            ServerLogActionEnum::REINSTALL,
            $variableData,
        );

        return new JsonResponse();
    }

    private function extractValidatedServerVariableData(Request $request, int $serverId): array
    {
        $server = $this->serverRepository->find($serverId);
        $variableData = $request->toArray();
        $isDataValid = isset($variableData['key']);
        $hasPermission = $server->getUser() === $this->getUser() || $this->isGranted(UserRoleEnum::ROLE_ADMIN->name);

        if (empty($server) || !$isDataValid || !$hasPermission) {
            throw new \Exception('Invalid data');
        }

        return [$server, $variableData];
    }
}
