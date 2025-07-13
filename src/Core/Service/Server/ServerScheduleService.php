<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylClientService;

class ServerScheduleService
{
    public function __construct(
        private readonly PterodactylClientService $pterodactylClientService,
        private readonly ServerLogService $serverLogService,
    ) {}

    public function getAllSchedules(Server $server, UserInterface $user): array
    {
        $schedules = $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->get(sprintf('servers/%s/schedules', $server->getPterodactylServerIdentifier()))
            ->toArray();

        return array_map(function ($schedule) {
            return $schedule->toArray();
        }, $schedules);
    }

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
            throw new \Exception('Harmonogramy są wyłączone dla tego serwera.');
        }

        $currentSchedules = $this->getAllSchedules($server, $user);
        if (count($currentSchedules) >= $schedulesLimit) {
            throw new \Exception(sprintf('Osiągnięto maksymalną liczbę harmonogramów (%d). Usuń istniejące harmonogramy, aby utworzyć nowe.', $schedulesLimit));
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

        $result = $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->post(sprintf('servers/%s/schedules', $server->getPterodactylServerIdentifier()), $scheduleData);

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

        return $result->toArray();
    }

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

        $result = $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->post(sprintf('servers/%s/schedules/%d', $server->getPterodactylServerIdentifier(), $scheduleId), $scheduleData);

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

        return $result->toArray();
    }

    public function deleteSchedule(
        Server $server,
        UserInterface $user,
        int $scheduleId
    ): void
    {
        $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->delete(sprintf('servers/%s/schedules/%d', $server->getPterodactylServerIdentifier(), $scheduleId));

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_SCHEDULE,
            [
                'schedule_id' => $scheduleId,
            ]
        );
    }

    public function getSchedule(
        Server $server,
        UserInterface $user,
        int $scheduleId
    ): array
    {
        $schedule = $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->get(sprintf('servers/%s/schedules/%d', $server->getPterodactylServerIdentifier(), $scheduleId));

        return $schedule->toArray();
    }

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
        $taskData = [
            'action' => $action,
            'payload' => $payload,
            'time_offset' => $timeOffset,
            'continue_on_failure' => $continueOnFailure,
        ];

        $result = $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->post(sprintf('servers/%s/schedules/%d/tasks/%d', $server->getPterodactylServerIdentifier(), $scheduleId, $taskId), $taskData);

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

        return $result->toArray();
    }

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
        $taskData = [
            'action' => $action,
            'payload' => $payload,
            'time_offset' => $timeOffset,
            'continue_on_failure' => $continueOnFailure,
        ];

        $result = $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->post(sprintf('servers/%s/schedules/%d/tasks', $server->getPterodactylServerIdentifier(), $scheduleId), $taskData);

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

        return $result->toArray();
    }

    public function deleteScheduleTask(
        Server $server,
        UserInterface $user,
        int $scheduleId,
        int $taskId
    ): void
    {
        $this->pterodactylClientService
            ->getApi($user)
            ->servers
            ->http
            ->delete(sprintf('servers/%s/schedules/%d/tasks/%d', $server->getPterodactylServerIdentifier(), $scheduleId, $taskId));

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_SCHEDULE_TASK,
            [
                'schedule_id' => $scheduleId,
                'task_id' => $taskId,
            ]
        );
    }
}
