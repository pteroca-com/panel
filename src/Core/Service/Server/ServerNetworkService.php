<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\DTO\Action\Result\ServerAllocationActionResult;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerNetworkService
{
    private const AUTO_ALLOCATION_DISABLED_ERROR = 'Server auto-allocation is not enabled for this instance.';

    private const DELETE_PRIMARY_ALLOCATION_ERROR = 'You cannot delete the primary allocation for this server.';

    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function createAllocation(
        Server $server,
        UserInterface $user,
    ): ServerAllocationActionResult
    {
        $endpointUrl = $this->getEndpointUrl($server, null);

        try {
            $this->pterodactylClientService
                ->getApi($user)
                ->servers
                ->http
                ->post($endpointUrl);
        } catch (Exception $exception) {
            $errorObject = json_decode($exception->getMessage(), true)['errors'][0] ?? null;
            $errorDetail = $errorObject['detail'] ?? null;


            if ($errorDetail === self::AUTO_ALLOCATION_DISABLED_ERROR) {
                $errorDetail = $this->translator->trans('pteroca.server.auto_allocation_disabled_for_this_instance');
            } else {
                $errorDetail = sprintf(
                    '%s: %s',
                    $this->translator->trans('pteroca.server.error_during_creating_allocation'),
                    $exception->getMessage(),
                );
            }
        }

        if (!empty($errorDetail)) {
            return new ServerAllocationActionResult(
                success: false,
                server: $server,
                error: $errorDetail,
            );
        }

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::CREATE_ALLOCATION,
        );

        return new ServerAllocationActionResult(
            success: true,
            server: $server,
        );
    }

    public function makePrimaryAllocation(
        Server $server,
        UserInterface $user,
        int $allocationId,
    ): ServerAllocationActionResult
    {
        $endpointUrl = $this->getEndpointUrl($server, $allocationId, 'primary');

        try {
            $this->pterodactylClientService
                ->getApi($user)
                ->servers
                ->http
                ->post($endpointUrl);
        } catch (Exception $exception) {
            $errorDetail = sprintf(
                '%s: %s',
                $this->translator->trans('pteroca.server.error_during_editing_allocation'),
                $exception->getMessage(),
            );
        }

        if (!empty($errorDetail)) {
            return new ServerAllocationActionResult(
                success: false,
                server: $server,
                error: $errorDetail,
            );
        }

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::MAKE_PRIMARY_ALLOCATION,
            [
                'allocationId' => $allocationId,
            ]
        );

        return new ServerAllocationActionResult(
            success: true,
            server: $server,
        );
    }

    public function editAllocation(
        Server $server,
        UserInterface $user,
        int $allocationId,
        string $notes,
    ): ServerAllocationActionResult
    {
        $endpointUrl = $this->getEndpointUrl($server, $allocationId);

        try {
            $this->pterodactylClientService
                ->getApi($user)
                ->servers
                ->http
                ->post($endpointUrl, [
                    'notes' => $notes,
                ]);
        } catch (Exception $exception) {
            $errorDetail = sprintf(
                '%s: %s',
                $this->translator->trans('pteroca.server.error_during_editing_allocation'),
                $exception->getMessage(),
            );
        }

        if (!empty($errorDetail)) {
            return new ServerAllocationActionResult(
                success: false,
                server: $server,
                error: $errorDetail,
            );
        }

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::EDIT_ALLOCATION,
            [
                'allocationId' => $allocationId,
                'notes' => $notes,
            ]
        );

        return new ServerAllocationActionResult(
            success: true,
            server: $server,
        );
    }

    public function deleteAllocation(
        Server $server,
        UserInterface $user,
        int $allocationId,
    ): ServerAllocationActionResult
    {
        $endpointUrl = $this->getEndpointUrl($server, $allocationId);

        try {
            $this->pterodactylClientService
                ->getApi($user)
                ->servers
                ->http
                ->delete($endpointUrl);
        } catch (Exception $exception) {
            $errorObject = json_decode($exception->getMessage(), true)['errors'][0] ?? null;
            $errorDetail = $errorObject['detail'] ?? null;

            if ($errorDetail === self::DELETE_PRIMARY_ALLOCATION_ERROR) {
                $errorDetail = $this->translator->trans('pteroca.server.cannot_delete_primary_allocation');
            } else {
                $errorDetail = sprintf(
                    '%s: %s',
                    $this->translator->trans('pteroca.server.error_during_deleting_allocation'),
                    $exception->getMessage(),
                );
            }
        }

        if (!empty($errorDetail)) {
            return new ServerAllocationActionResult(
                success: false,
                server: $server,
                error: $errorDetail,
            );
        }

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_ALLOCATION,
            [
                'allocationId' => $allocationId,
            ]
        );

        return new ServerAllocationActionResult(
            success: true,
            server: $server,
        );
    }

    private function getEndpointUrl(Server $server, ?int $allocationId, string $additionalUri = ''): string
    {
        $endpointUrl = sprintf(
            'servers/%s/network/allocations',
            $server->getPterodactylServerIdentifier(),
        );

        if (!empty($allocationId)) {
            $endpointUrl = sprintf(
                '%s/%d',
                $endpointUrl,
                $allocationId,
            );
        }

        if (!empty($additionalUri)) {
            $endpointUrl = sprintf(
                '%s/%s',
                $endpointUrl,
                $additionalUri,
            );
        }

        return $endpointUrl;
    }
}
