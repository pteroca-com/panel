<?php

namespace App\Core\Service\Server;

use App\Core\DTO\Action\Result\ServerAllocationActionResult;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServerNetworkService
{
    private const DELETE_PRIMARY_ALLOCATION_ERROR = 'You cannot delete the primary allocation for this server.';

    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function editAllocation(
        Server $server,
        User $user,
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
            $errorDetail = $this->translator->trans('pteroca.server.error_during_editing_allocation');
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
        User $user,
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
                $errorDetail = $this->translator->trans('pteroca.server.error_during_deleting_allocation');
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

    private function getEndpointUrl(Server $server, int $allocationId): string
    {
        return sprintf(
            'servers/%s/network/allocations/%d',
            $server->getPterodactylServerIdentifier(),
            $allocationId,
        );
    }
}
