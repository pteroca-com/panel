<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Event\Server\Schedule\ServerScheduleCreationRequestedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleCreatedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleCreationFailedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleUpdateRequestedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleUpdatedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleUpdateFailedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleDeletionRequestedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleDeletedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleDeletionFailedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskCreationRequestedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskCreatedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskCreationFailedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskUpdateRequestedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskUpdatedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskUpdateFailedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskDeletionRequestedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskDeletedEvent;
use App\Core\Event\Server\Schedule\ServerScheduleTaskDeletionFailedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class ServerScheduleService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
        private ServerLogService              $serverLogService,
        private EventDispatcherInterface      $eventDispatcher,
        private RequestStack                  $requestStack,
        private EventContextService           $eventContextService,
    ) {}

    public function getAllSchedules(Server $server, UserInterface $user): array
    {
        $schedules = $this->pterodactylApplicationService
            ->getClientApi($user)
            ->schedules()
            ->getSchedules($server->getPterodactylServerIdentifier())
            ->toArray();

        return array_map(function ($schedule) {
            return $schedule->toArray();
        }, $schedules);
    }

    /**
     * @throws Exception
     */
    public function createSchedule(
        Server $server,
        UserInterface $user,
        string $name,
        array $cronExpression,
        bool $isActive = true,
        bool $onlyWhenOnline = true
    ): array
    {
        $schedulesLimit = $server->getServerProduct()->getSchedules();
        if ($schedulesLimit <= 0) {
            throw new Exception('Schedules are disabled for this server.');
        }

        $currentSchedules = $this->getAllSchedules($server, $user);
        if (count($currentSchedules) >= $schedulesLimit) {
            throw new Exception(sprintf('Maximum number of schedules (%d) reached. Delete existing schedules to create new ones.', $schedulesLimit));
        }

        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerScheduleCreationRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $name,
            $cronExpression,
            $isActive,
            $onlyWhenOnline,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Schedule creation was blocked';
            throw new RuntimeException($reason);
        }

        $scheduleData = [
            'name' => $name,
            'day_of_week' => $cronExpression['day_of_week'],
            'month' => $cronExpression['month'],
            'day_of_month' => $cronExpression['day_of_month'],
            'hour' => $cronExpression['hour'],
            'minute' => $cronExpression['minute'],
            'is_active' => $isActive,
            'only_when_online' => $onlyWhenOnline,
        ];

        try {
            $result = $this->pterodactylApplicationService
                ->getClientApi($user)
                ->schedules()
                ->createSchedule($server, $scheduleData);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::CREATE_SCHEDULE,
                [
                    'schedule_name' => $name,
                    'cron_expression' => implode(' ', $cronExpression),
                    'is_active' => $isActive,
                    'only_when_online' => $onlyWhenOnline,
                ]
            );

            $resultArray = $result->toArray();
            $scheduleId = $resultArray['attributes']['id'] ?? 0;

            $createdEvent = new ServerScheduleCreatedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $name,
                $cronExpression,
                $isActive,
                $onlyWhenOnline,
                $context
            );
            $this->eventDispatcher->dispatch($createdEvent);

            return $resultArray;
        } catch (Exception $e) {
            $failedEvent = new ServerScheduleCreationFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $name,
                $cronExpression,
                $isActive,
                $onlyWhenOnline,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function updateSchedule(
        Server $server,
        UserInterface $user,
        int $scheduleId,
        ?string $name = null,
        ?array $cronExpression = null,
        ?bool $isActive = null,
        ?bool $onlyWhenOnline = null
    ): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $cronExpressionForEvent = $cronExpression ?? [];

        $requestedEvent = new ServerScheduleUpdateRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $scheduleId,
            $name ?? '',
            $cronExpressionForEvent,
            $isActive ?? true,
            $onlyWhenOnline ?? true,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Schedule update was blocked';
            throw new RuntimeException($reason);
        }

        $scheduleData = [];

        if ($name !== null) {
            $scheduleData['name'] = $name;
        }

        if ($cronExpression !== null) {
            $scheduleData['minute'] = $cronExpression['minute'] ?? '*';
            $scheduleData['hour'] = $cronExpression['hour'] ?? '*';
            $scheduleData['day_of_month'] = $cronExpression['day_of_month'] ?? '*';
            $scheduleData['month'] = $cronExpression['month'] ?? '*';
            $scheduleData['day_of_week'] = $cronExpression['day_of_week'] ?? '*';
        }

        if ($isActive !== null) {
            $scheduleData['is_active'] = $isActive;
        }

        if ($onlyWhenOnline !== null) {
            $scheduleData['only_when_online'] = $onlyWhenOnline;
        }

        try {
            $result = $this->pterodactylApplicationService
                ->getClientApi($user)
                ->schedules()
                ->updateSchedule($server, $scheduleId, $scheduleData);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::UPDATE_SCHEDULE,
                [
                    'schedule_id' => $scheduleId,
                    'schedule_name' => $name,
                    'cron_expression' => $cronExpression ? implode(' ', $cronExpression) : null,
                    'is_active' => $isActive,
                    'only_when_online' => $onlyWhenOnline,
                ]
            );

            $updatedEvent = new ServerScheduleUpdatedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $name ?? '',
                $cronExpressionForEvent,
                $isActive ?? true,
                $onlyWhenOnline ?? true,
                $context
            );
            $this->eventDispatcher->dispatch($updatedEvent);

            return $result->toArray();
        } catch (Exception $e) {
            $failedEvent = new ServerScheduleUpdateFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $name ?? '',
                $cronExpressionForEvent,
                $isActive ?? true,
                $onlyWhenOnline ?? true,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function deleteSchedule(
        Server $server,
        UserInterface $user,
        int $scheduleId
    ): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerScheduleDeletionRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $scheduleId,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Schedule deletion was blocked';
            throw new RuntimeException($reason);
        }

        try {
            $this->pterodactylApplicationService
                ->getClientApi($user)
                ->schedules()
                ->deleteSchedule($server, $scheduleId);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::DELETE_SCHEDULE,
                [
                    'schedule_id' => $scheduleId,
                ]
            );

            $deletedEvent = new ServerScheduleDeletedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $context
            );
            $this->eventDispatcher->dispatch($deletedEvent);
        } catch (Exception $e) {
            $failedEvent = new ServerScheduleDeletionFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

            throw $e;
        }
    }

    public function getSchedule(
        Server $server,
        UserInterface $user,
        int $scheduleId
    ): array
    {
        return $this->pterodactylApplicationService
            ->getClientApi($user)
            ->schedules()
            ->getSchedule($server, $scheduleId)
            ->toArray();
    }

    /**
     * @throws Exception
     */
    public function updateScheduleTask(
        Server $server,
        UserInterface $user,
        int $scheduleId,
        int $taskId,
        string $action,
        string $payload,
        int $timeOffset = 0,
        bool $continueOnFailure = false
    ): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerScheduleTaskUpdateRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $scheduleId,
            $taskId,
            $action,
            $payload,
            $timeOffset,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Schedule task update was blocked';
            throw new RuntimeException($reason);
        }

        $taskData = [
            'action' => $action,
            'payload' => $payload,
            'time_offset' => $timeOffset,
            'continue_on_failure' => $continueOnFailure,
        ];

        try {
            $result = $this->pterodactylApplicationService
                ->getClientApi($user)
                ->schedules()
                ->updateScheduleTask($server, $scheduleId, $taskId, $taskData);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::UPDATE_SCHEDULE_TASK,
                [
                    'schedule_id' => $scheduleId,
                    'task_id' => $taskId,
                    'action' => $action,
                    'payload' => $payload,
                    'time_offset' => $timeOffset,
                    'continue_on_failure' => $continueOnFailure,
                ]
            );

            $updatedEvent = new ServerScheduleTaskUpdatedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $taskId,
                $action,
                $payload,
                $timeOffset,
                $context
            );
            $this->eventDispatcher->dispatch($updatedEvent);

            return $result->toArray();
        } catch (Exception $e) {
            $failedEvent = new ServerScheduleTaskUpdateFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $taskId,
                $action,
                $payload,
                $timeOffset,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function createScheduleTask(
        Server $server,
        UserInterface $user,
        int $scheduleId,
        string $action,
        string $payload,
        int $timeOffset = 0,
        bool $continueOnFailure = false
    ): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerScheduleTaskCreationRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $scheduleId,
            $action,
            $payload,
            $timeOffset,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Schedule task creation was blocked';
            throw new RuntimeException($reason);
        }

        $taskData = [
            'action' => $action,
            'payload' => $payload,
            'time_offset' => $timeOffset,
            'continue_on_failure' => $continueOnFailure,
        ];

        try {
            $result = $this->pterodactylApplicationService
                ->getClientApi($user)
                ->schedules()
                ->createScheduleTask($server, $scheduleId, $taskData);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::CREATE_SCHEDULE_TASK,
                [
                    'schedule_id' => $scheduleId,
                    'action' => $action,
                    'payload' => $payload,
                    'time_offset' => $timeOffset,
                    'continue_on_failure' => $continueOnFailure,
                ]
            );

            $resultArray = $result->toArray();
            $taskId = $resultArray['attributes']['id'] ?? 0;

            $createdEvent = new ServerScheduleTaskCreatedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $taskId,
                $action,
                $payload,
                $timeOffset,
                $context
            );
            $this->eventDispatcher->dispatch($createdEvent);

            return $resultArray;
        } catch (Exception $e) {
            $failedEvent = new ServerScheduleTaskCreationFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $action,
                $payload,
                $timeOffset,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function deleteScheduleTask(
        Server $server,
        UserInterface $user,
        int $scheduleId,
        int $taskId
    ): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerScheduleTaskDeletionRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $scheduleId,
            $taskId,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Schedule task deletion was blocked';
            throw new RuntimeException($reason);
        }

        try {
            $this->pterodactylApplicationService
                ->getClientApi($user)
                ->schedules()
                ->deleteScheduleTask($server, $scheduleId, $taskId);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::DELETE_SCHEDULE_TASK,
                [
                    'schedule_id' => $scheduleId,
                    'task_id' => $taskId,
                ]
            );

            $deletedEvent = new ServerScheduleTaskDeletedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $taskId,
                $context
            );
            $this->eventDispatcher->dispatch($deletedEvent);
        } catch (Exception $e) {
            $failedEvent = new ServerScheduleTaskDeletionFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $scheduleId,
                $taskId,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);

            throw $e;
        }
    }
}
