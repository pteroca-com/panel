<?php

namespace App\Core\Service\Server;

use App\Core\DTO\Action\Result\UpdateServerActionResult;
use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Enum\CrudFlashMessageTypeEnum;
use App\Core\Service\Pterodactyl\PterodactylService;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Timdesm\PterodactylPhpApi\Resources\Server as PterodactylServer;

class UpdateServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerBuildService $serverBuildService,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function updateServer(Server|ServerProduct $entityInstance): UpdateServerActionResult
    {
        $pterodactylServerId = $entityInstance instanceof Server
            ? $entityInstance->getPterodactylServerId()
            : $entityInstance->getServer()->getPterodactylServerId();
        $pterodactylServer = $this->getPterodactylServerDetails($pterodactylServerId);
        $updateServerActionResult = new UpdateServerActionResult();

        return match (true) {
            $entityInstance instanceof Server => $this->updateByServerEntity(
                $updateServerActionResult,
                $entityInstance,
                $pterodactylServer
            ),
            $entityInstance instanceof ServerProduct => $this->updateByServerProductEntity(
                $updateServerActionResult,
                $entityInstance,
                $pterodactylServer
            ),
            default => throw new \InvalidArgumentException('Invalid entity type'),
        };
    }

    private function updateByServerProductEntity(
        UpdateServerActionResult $updateServerActionResult,
        ServerProduct $entityInstance,
        PterodactylServer $pterodactylServer,
    ): UpdateServerActionResult
    {
        $updateServerActionResult = $this->updateServerBuild(
            $updateServerActionResult,
            $entityInstance,
            $pterodactylServer
        );

        $updateServerActionResult = $this->updateServerStartup(
            $updateServerActionResult,
            $entityInstance,
            $pterodactylServer
        );

        return $this->updateByServerEntity(
            $updateServerActionResult,
            $entityInstance->getServer(),
            $pterodactylServer
        );
    }

    private function updateByServerEntity(
        UpdateServerActionResult $updateServerActionResult,
        Server $entityInstance,
        PterodactylServer $pterodactylServer,
    ): UpdateServerActionResult
    {
        $updateServerActionResult = $this->setServerSuspendedStatus(
            $updateServerActionResult,
            $entityInstance,
            $pterodactylServer
        );

        return $this->updateServerDetails(
            $updateServerActionResult,
            $entityInstance,
            $pterodactylServer
        );
    }

    private function getPterodactylServerDetails(int $serverId): PterodactylServer
    {
        /** @var PterodactylServer $server */
        $server = $this->pterodactylService->getApi()->servers->get($serverId);
        return $server;
    }

    private function updateServerBuild(
        UpdateServerActionResult $updateServerActionResult,
        ServerProduct $entityInstance,
        PterodactylServer $pterodactylServer,
    ): UpdateServerActionResult
    {
        try {
            $updatedServerBuild = $this->serverBuildService
                ->prepareUpdateServerBuild($entityInstance, $pterodactylServer);
            $this->pterodactylService
                ->getApi()
                ->servers
                ->updateBuild($entityInstance->getServer()->getPterodactylServerId(), $updatedServerBuild);
            $updateServerActionResult->addMessage(
                $this->translator->trans('pteroca.crud.server.build_updated_successfully'),
                CrudFlashMessageTypeEnum::SUCCESS,
            );
        } catch (Exception $exception) {
            $errorMessage = sprintf(
                '%s %s',
                $this->translator->trans('pteroca.crud.server.build_update_error'),
                $exception->getMessage()
            );
            $updateServerActionResult->addMessage($errorMessage, CrudFlashMessageTypeEnum::DANGER);
        } finally {
            return $updateServerActionResult;
        }
    }

    private function updateServerStartup(
        UpdateServerActionResult $updateServerActionResult,
        ServerProduct $entityInstance,
        PterodactylServer $pterodactylServer,
    ): UpdateServerActionResult
    {
        try {
            $updatedServerStartup = $this->serverBuildService
                ->prepareUpdateServerStartup($entityInstance, $pterodactylServer);

            $this->pterodactylService
                ->getApi()
                ->servers
                ->updateStartup($entityInstance->getServer()->getPterodactylServerId(), $updatedServerStartup);

            $updateServerActionResult->addMessage(
                $this->translator->trans('pteroca.crud.server.startup_updated_successfully'),
                CrudFlashMessageTypeEnum::SUCCESS,
            );
        } catch (Exception $exception) {
            $errorMessage = sprintf(
                '%s %s',
                $this->translator->trans('pteroca.crud.server.startup_update_error'),
                $exception->getMessage(),
            );
            $updateServerActionResult->addMessage($errorMessage, CrudFlashMessageTypeEnum::DANGER);
        } finally {
            return $updateServerActionResult;
        }
    }

    private function setServerSuspendedStatus(
        UpdateServerActionResult $updateServerActionResult,
        Server $entityInstance,
        PterodactylServer $pterodactylServer,
    ): UpdateServerActionResult
    {
        if ($entityInstance->getIsSuspended() !== $pterodactylServer->get('suspended')) {
            if ($entityInstance->getIsSuspended()) {
                try {
                    $this->pterodactylService->getApi()->servers->suspend($entityInstance->getPterodactylServerId());
                    $updateServerActionResult->addMessage(
                        $this->translator->trans('pteroca.crud.server.suspended_successfully'),
                        CrudFlashMessageTypeEnum::SUCCESS,
                    );
                } catch (\Exception $exception) {
                    $errorMessage = sprintf(
                        '%s %s',
                        $this->translator->trans('pteroca.crud.server.suspended_error'),
                        $exception->getMessage(),
                    );
                    $updateServerActionResult->addMessage($errorMessage, CrudFlashMessageTypeEnum::DANGER);
                }
            } else {
                try {
                    $this->pterodactylService->getApi()->servers->unsuspend($entityInstance->getPterodactylServerId());
                    $updateServerActionResult->addMessage(
                        $this->translator->trans('pteroca.crud.server.unsuspended_successfully'),
                        CrudFlashMessageTypeEnum::SUCCESS,
                    );
                } catch (Exception $exception) {
                    $errorMessage = sprintf(
                        '%s %s',
                        $this->translator->trans('pteroca.crud.server.unsuspended_error'),
                        $exception->getMessage(),
                    );
                    $updateServerActionResult->addMessage($errorMessage, CrudFlashMessageTypeEnum::DANGER);
                }
            }
        }

        return $updateServerActionResult;
    }
    private function updateServerDetails(
        UpdateServerActionResult $updateServerActionResult,
        Server $entityInstance,
        PterodactylServer $pterodactylServer,
    ): UpdateServerActionResult
    {
        if ($entityInstance->getUser()->getPterodactylUserId() !== $pterodactylServer->get('user')) {
            try {
                $this->pterodactylService->getApi()->servers->updateDetails(
                    $entityInstance->getPterodactylServerId(),
                    [
                        'name' => $pterodactylServer->get('name'),
                        'description' => $pterodactylServer->get('description'),
                        'user' => $entityInstance->getUser()->getPterodactylUserId(),
                    ],
                );
                $updateServerActionResult->addMessage(
                    $this->translator->trans('pteroca.crud.server.details_updated_successfully'),
                    CrudFlashMessageTypeEnum::SUCCESS,
                );
            } catch (Exception $exception) {
                $errorMessage = sprintf(
                    '%s %s',
                    $this->translator->trans('pteroca.crud.server.details_update_error'),
                    $exception->getMessage(),
                );
                $updateServerActionResult->addMessage($errorMessage, CrudFlashMessageTypeEnum::DANGER);
            }
        }

        return $updateServerActionResult;
    }
}
