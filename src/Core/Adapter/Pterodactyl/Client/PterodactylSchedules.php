<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylSchedulesInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylSchedule;
use App\Core\DTO\Pterodactyl\Client\PterodactylScheduleTask;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylSchedules extends AbstractPterodactylClientAdapter implements PterodactylSchedulesInterface
{
    public function getSchedules(string $serverId): Collection
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/schedules");
        $data = $this->validateListResponse($response, 200);

        $schedules = [];
        foreach ($data['data'] as $scheduleData) {
            $schedules[] = new PterodactylSchedule($scheduleData['attributes']);
        }

        return new Collection($schedules, $this->getMetaFromResponse($data));
    }

    public function getSchedule(string $serverId, int $scheduleId): PterodactylSchedule
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/schedules/{$scheduleId}");
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylSchedule($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function createSchedule(string $serverId, array $scheduleData): PterodactylSchedule
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/schedules", ['json' => $scheduleData]);
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylSchedule($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function updateSchedule(string $serverId, int $scheduleId, array $scheduleData): PterodactylSchedule
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/schedules/{$scheduleId}", ['json' => $scheduleData]);
        $data = $this->validateClientResponse($response, 200);

        return new PterodactylSchedule($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function deleteSchedule(string $serverId, int $scheduleId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/{$serverId}/schedules/{$scheduleId}");
        return $response->getStatusCode() === 204;
    }

    public function executeSchedule(string $serverId, int $scheduleId): bool
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/schedules/{$scheduleId}/execute");
        return $response->getStatusCode() === 204;
    }

    public function getScheduleTasks(string $serverId, int $scheduleId): Collection
    {
        $response = $this->makeRequest('GET', "servers/{$serverId}/schedules/{$scheduleId}/tasks");
        $data = $this->validateListResponse($response, 200);

        return new Collection($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function createScheduleTask(string $serverId, int $scheduleId, array $taskData): PterodactylScheduleTask
    {
        $response = $this->makeRequest('POST', "servers/{$serverId}/schedules/{$scheduleId}/tasks", ['json' => $taskData]);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to create schedule task');
        }

        $data = $response->toArray();
        return new PterodactylScheduleTask($this->getDataFromResponse($data) ?: $data);
    }

    public function updateScheduleTask(string $serverId, int $scheduleId, int $taskId, array $taskData): PterodactylScheduleTask
    {
        $response = $this->makeRequest('PATCH', "servers/{$serverId}/schedules/{$scheduleId}/tasks/{$taskId}", ['json' => $taskData]);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to update schedule task');
        }

        $data = $response->toArray();
        return new PterodactylScheduleTask($this->getDataFromResponse($data) ?: $data);
    }

    public function deleteScheduleTask(string $serverId, int $scheduleId, int $taskId): bool
    {
        $response = $this->makeRequest('DELETE', "servers/{$serverId}/schedules/{$scheduleId}/tasks/{$taskId}");
        return $response->getStatusCode() === 204;
    }
}
