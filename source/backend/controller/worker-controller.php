<?php

// TODO: CHECK IF THE REQUEST HAS PROJECT ID;
// IF NOT, RETURN UNASSIGNED WORKERS

class WorkerController implements Controller
{

    private function __construct()
    {
    }

    public static function index(): void
    {
    }

    // Used to fetch single worker info by ID (eg. /get-worker-info/1)
    public static function getWorkerById($args = []): void
    {
        if (!isset($args['workerId'])) {
            Response::error('Worker ID is required');
        }

        $workerId = $args['workerId'];
        $worker = UserModel::all()[0];
        Response::success(
            [
                self::createResponseArrayData($worker->toWorker())
            ],
            'Worker info retrieved successfully'
        );
    }

    // Used to fetch multiple workers by IDs or name filter (eg. /get-worker-info?ids=1,2,3 or /get-worker-info?name=John)
    public static function getWorkerByKey(): void
    {
        $workers = UserModel::all();

        $workerIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
        $name = $_GET['name'] ?? null;

        $return = [];
        // If both ID and name filters are empty, return all workers
        if (empty($workerIds) && empty($name)) {
            foreach ($workers as $worker) {
                $return[] = self::createResponseArrayData($worker->toWorker());
            }
        } else {
            // TODO: Fetch workers by IDs and/or name from the database
            foreach ($workers as $worker) {
                $return[] = self::createResponseArrayData($worker->toWorker());
            }
        }

        Response::success($return, 'Worker info retrieved successfully');
    }

    private static function createResponseArrayData(Worker $worker): array
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

    public static function addWorkerToProject(array $args = []): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        if (!isset($args['projectId'])) {
            Response::error('Project ID is required');
        }

        $workerIds = $data['workerIds'] ?? null;
        if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
            Response::error('Worker IDs are required');
        }

        $returnData = $data['returnData'] ?? false;

        // TODO: Add worker to project logic

        $returnDataArray = [];
        if ($returnData) {
            foreach ($workerIds as $workerId) {
                // TODO: Fetch User
                $user = UserModel::all()[0];

                $userPerformance = WorkerPerformanceCalculator::calculateWorkerPerformance(TaskModel::all());
                $returnDataArray[] = [
                    'id' => $user->getPublicId(),
                    'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                    'profilePicture' => $user->getProfileLink(),
                    'bio' => $user->getBio(),
                    'email' => $user->getEmail(),
                    'contactNumber' => $user->getContactNumber(),
                    'role' => $user->getRole()->value,
                    'jobTitles' => $user->getJobTitles()->toArray(),
                    'totalTasks' => count(TaskModel::all()),
                    'completedTasks' => TaskModel::all()->getTaskCountByStatus(WorkStatus::COMPLETED),
                    'performance' => $userPerformance['overallScore'],
                ];
            }

        }

        Response::success($returnDataArray, 'Worker added successfully');
    }

    public static function addWorkerToTask(array $args = []): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        if (!isset($args['projectId'])) {
            Response::error('Project ID is required');
        }

        if (!isset($args['taskId'])) {
            Response::error('Task ID is required');
        }

        $workerIds = $data['workerIds'] ?? null;
        if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
            Response::error('Worker IDs are required');
        }

        $returnData = $data['returnData'] ?? false;

        // TODO: Add worker to project logic

        $returnDataArray = [];
        if ($returnData) {
            foreach ($workerIds as $workerId) {
                // TODO: Fetch User
                $user = UserModel::all()[0];

                $userPerformance = WorkerPerformanceCalculator::calculateWorkerPerformance(TaskModel::all());
                $returnDataArray[] = [
                    'id' => $user->getPublicId(),
                    'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                    'profilePicture' => $user->getProfileLink(),
                    'bio' => $user->getBio(),
                    'email' => $user->getEmail(),
                    'contactNumber' => $user->getContactNumber(),
                    'role' => $user->getRole()->value,
                    'jobTitles' => $user->getJobTitles()->toArray(),
                    'totalTasks' => count(TaskModel::all()),
                    'completedTasks' => TaskModel::all()->getTaskCountByStatus(WorkStatus::COMPLETED),
                    'performance' => $userPerformance['overallScore'],
                ];
            }

        }

        Response::success($returnDataArray, 'Worker added successfully');
    }

    public static function editWorker(array $args = []): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        Response::success([], 'Worker updated successfully');
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