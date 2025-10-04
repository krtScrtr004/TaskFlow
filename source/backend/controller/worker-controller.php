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
                'id' => $worker->getPublicId(),
                'name' => $worker->getFirstName() . ' ' . $worker->getLastName(),
                'profilePicture' => $worker->getProfileLink(),
                'bio' => $worker->getBio(),
                'email' => $worker->getEmail(),
                'contactNumber' => $worker->getContactNumber(),
                'role' => $worker->getRole()->value,
                'jobTitles' => $worker->getJobTitles()->toArray(),
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

    public static function addWorker(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        if (!isset($data['projectId'])) {
            Response::error('Project ID is required');
        }

        if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
            Response::error('Worker IDs are required');
        }

        // TODO: Add worker to project logic

        Response::success([
            'message' => 'Worker added successfully'
        ], 'Worker added successfully');
    }

    public static function terminateWorker(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        if (!isset($data['projectId'])) {
            Response::error('Project ID is required');
        }

        if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
            Response::error('Worker IDs are required');
        }

        // TODO: Terminate worker from project logic

        Response::success([
            'message' => 'Worker terminated successfully'
        ], 'Worker terminated successfully');
    }
}