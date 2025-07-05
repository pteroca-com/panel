<?php

namespace App\Core\Controller\API;

use App\Core\Enum\ServerPermissionEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Server\ServerScheduleService;
use App\Core\Trait\InternalServerApiTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerScheduleController extends APIAbstractController
{
    use InternalServerApiTrait;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerScheduleService $serverScheduleService,
    ) {}

    #[Route('/panel/api/server/{id}/schedules/create', name: 'server_schedules_create', methods: ['POST'])]
    public function createSchedule(int $id, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SCHEDULE_CREATE);
        $response = new JsonResponse();
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || empty($data['name'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Schedule name is required']);
            return $response;
        }

        // Walidacja pól cron
        $cronFields = ['minute', 'hour', 'day_of_month', 'month', 'day_of_week'];
        foreach ($cronFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $response->setStatusCode(400);
                $response->setData(['error' => "Cron field '{$field}' is required"]);
                return $response;
            }
        }

        try {
            $cronExpression = [
                'minute' => $data['minute'],
                'hour' => $data['hour'],
                'day_of_month' => $data['day_of_month'],
                'month' => $data['month'],
                'day_of_week' => $data['day_of_week'],
            ];

            $result = $this->serverScheduleService->createSchedule(
                $server,
                $this->getUser(),
                $data['name'],
                $cronExpression,
                $data['is_active'] ?? true,
                $data['only_when_online'] ?? true
            );

            $response->setData(['success' => true, 'schedule' => $result]);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/schedules/{scheduleId}', name: 'server_schedules_update', methods: ['PUT'])]
    public function updateSchedule(int $id, int $scheduleId, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SCHEDULE_UPDATE);
        $response = new JsonResponse();
        
        $data = json_decode($request->getContent(), true);

        try {
            $cronExpression = null;
            if (isset($data['minute']) || isset($data['hour']) || isset($data['day_of_month']) || 
                isset($data['month']) || isset($data['day_of_week'])) {
                $cronExpression = [
                    'minute' => $data['minute'] ?? '*',
                    'hour' => $data['hour'] ?? '*',
                    'day_of_month' => $data['day_of_month'] ?? '*',
                    'month' => $data['month'] ?? '*',
                    'day_of_week' => $data['day_of_week'] ?? '*',
                ];
            }

            $result = $this->serverScheduleService->updateSchedule(
                $server,
                $this->getUser(),
                $scheduleId,
                $data['name'] ?? null,
                $cronExpression,
                $data['is_active'] ?? null,
                $data['only_when_online'] ?? null
            );

            $response->setData(['success' => true, 'schedule' => $result]);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/schedules/{scheduleId}/delete', name: 'server_schedules_delete', methods: ['DELETE'])]
    public function deleteSchedule(int $id, int $scheduleId): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SCHEDULE_DELETE);
        $response = new JsonResponse();

        try {
            $this->serverScheduleService->deleteSchedule($server, $this->getUser(), $scheduleId);
            $response->setData(['success' => true]);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/schedules/{scheduleId}', name: 'server_schedules_get', methods: ['GET'])]
    public function getSchedule(int $id, int $scheduleId): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SCHEDULE_READ);
        $response = new JsonResponse();

        try {
            $schedule = $this->serverScheduleService->getSchedule($server, $this->getUser(), $scheduleId);
            $response->setData($schedule);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}', name: 'server_schedule_tasks_update', methods: ['PUT'])]
    public function updateScheduleTask(int $id, int $scheduleId, int $taskId, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SCHEDULE_UPDATE);
        $response = new JsonResponse();
        
        $data = json_decode($request->getContent(), true);

        if (!isset($data['action']) || empty($data['action'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Action is required']);
            return $response;
        }

        if (!isset($data['payload']) || empty($data['payload'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Payload is required']);
            return $response;
        }

        try {
            $result = $this->serverScheduleService->updateScheduleTask(
                $server,
                $this->getUser(),
                $scheduleId,
                $taskId,
                $data['action'],
                $data['payload'],
                $data['time_offset'] ?? 0,
                $data['continue_on_failure'] ?? false
            );

            $response->setData(['success' => true, 'task' => $result]);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('/panel/api/server/{id}/schedules/{scheduleId}/tasks', name: 'server_schedule_tasks_create', methods: ['POST'])]
    public function createScheduleTask(int $id, int $scheduleId, Request $request): JsonResponse
    {
        $server = $this->getServer($id, ServerPermissionEnum::SCHEDULE_UPDATE);
        $response = new JsonResponse();
        
        $data = json_decode($request->getContent(), true);

        if (!isset($data['action']) || empty($data['action'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Action is required']);
            return $response;
        }

        if (!isset($data['payload']) || empty($data['payload'])) {
            $response->setStatusCode(400);
            $response->setData(['error' => 'Payload is required']);
            return $response;
        }

        try {
            $result = $this->serverScheduleService->createScheduleTask(
                $server,
                $this->getUser(),
                $scheduleId,
                $data['action'],
                $data['payload'],
                $data['time_offset'] ?? 0,
                $data['continue_on_failure'] ?? false
            );

            $response->setData(['success' => true, 'task' => $result]);
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }
}
