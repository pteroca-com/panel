<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylSchedule;
use App\Core\DTO\Pterodactyl\Client\PterodactylScheduleTask;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylSchedulesInterface
{
    public function getSchedules(string $serverId): Collection;

    public function getSchedule(string $serverId, int $scheduleId): PterodactylSchedule;

    public function createSchedule(string $serverId, array $scheduleData): PterodactylSchedule;

    public function updateSchedule(string $serverId, int $scheduleId, array $scheduleData): PterodactylSchedule;

    public function deleteSchedule(string $serverId, int $scheduleId): bool;

    public function executeSchedule(string $serverId, int $scheduleId): bool;

    public function getScheduleTasks(string $serverId, int $scheduleId): Collection;

    public function createScheduleTask(string $serverId, int $scheduleId, array $taskData): PterodactylScheduleTask;

    public function updateScheduleTask(string $serverId, int $scheduleId, int $taskId, array $taskData): PterodactylScheduleTask;

    public function deleteScheduleTask(string $serverId, int $scheduleId, int $taskId): bool;
}
