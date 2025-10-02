<?php

class WorkerController implements Controller
{

    private function __construct()
    {
    }

    public static function index(): void
    {
    }

    public static function getWorkerInfo($workerId): void
    {
        if (!$workerId) {
            Response::error('Worker ID is required');
        }

        // TODO: Dummy
        $worker = UserModel::all()[0];
        $worker->setRole(Role::WORKER);
        $tasks = TaskModel::all();
        $workerPerformanceTask = WorkerPerformanceCalculator::calculateWorkerPerformance($tasks);

        Response::success([
            'id' => $worker->getId(),
            'name' => $worker->getFirstName() . ' ' . $worker->getLastName(),
            'profilePicture' => $worker->getProfileLink(),
            'bio' => $worker->getBio(),
            'email' => $worker->getEmail(),
            'contactNumber' => $worker->getContactNumber(),
            'role' => $worker->getRole()->value,
            'totalTasks' => count($tasks),
            'completedTasks' => $tasks->getTaskCountByStatus(WorkStatus::COMPLETED),
            'performance' => $workerPerformanceTask['overallScore'],
        ], 'Worker info retrieved successfully');
    }
}