<?php

namespace App\Core\Service\Server;

use App\Core\DTO\Action\Result\DeleteServerAllocationResult;
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

    public function deleteAllocation(
        Server $server,
        User $user,
        int $allocationId,
    ): DeleteServerAllocationResult
    {
        $endpointUrl = sprintf(
            'servers/%s/network/allocations/%d',
            $server->getPterodactylServerIdentifier(),
            $allocationId,
        );

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
            return new DeleteServerAllocationResult(
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

        return new DeleteServerAllocationResult(
            success: true,
            server: $server,
        );
    }
}
