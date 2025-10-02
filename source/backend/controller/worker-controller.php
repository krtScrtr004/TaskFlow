<?php

class WorkerController implements Controller
{

    private function __construct()
    {
    }

    public static function index(): void
    {
    }

    public static function getWorkerInfo($args = []): void
    {
        $key = $args['key'] ? trim($args['key']) : null;

        // TODO: Dummy
        $workers = UserModel::all();

        function createResponseArrayData(Worker $worker): array
        {
            $worker->setRole(Role::WORKER);
            $tasks = TaskModel::all();
            $workerPerformanceTask = WorkerPerformanceCalculator::calculateWorkerPerformance($tasks);
            return [
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
            ];
        }

        // If no key (Name / ID) provided - return all
        if (!$key || empty($key)) {
            $return = [];
            foreach ($workers as $worker) {
                $return[] = createResponseArrayData($worker->toWorker());
            }
            Response::success($return, 'Worker info retrieved successfully');
        } else {
            $worker = $workers[0];
            Response::success([
                createResponseArrayData($worker->toWorker())
            ], 'Worker info retrieved successfully');
        }
    }
}