<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Enumeration\WorkStatus;
use App\Model\WorkerModel;
use App\Utility\WorkerPerformanceCalculator;
use Exception;

// TODO: CHECK IF THE REQUEST HAS PROJECT ID;
// IF NOT, RETURN UNASSIGNED WORKERS
class WorkerEndpoint
{
    private static function createResponseArrayData(Worker $worker): array
    {
        $worker->setRole(Role::WORKER);
        $projects = ProjectModel::all();
        $workerPerformanceProject = WorkerPerformanceCalculator::calculate($projects);
        return [
            ...$worker->toArray(),
            'totalProjects' => $workerPerformanceProject['totalProjects'],
            'completedProjects' => $projects->getCountByStatus(WorkStatus::COMPLETED),
            'performance' => $workerPerformanceProject['overallScore'],
        ];
    }

    // TODO:
    public static function getProjectWorkerById($args = []): void
    {
        // try {
        //     if (!HttpAuth::isGETRequest()) {
        //         throw new ForbiddenException('Invalid request method. GET request required.');
        //     }

        //     if (!SessionAuth::hasAuthorizedSession()) {
        //         throw new ForbiddenException('User session is not authorized to perform this action.');
        //     }

        //     $projectId = isset($args['projectId'])
        //         ? UUID::fromString($args['projectId'])
        //         : null;
        //     if (!$projectId) {
        //         throw new ForbiddenException('Project ID is required.');
        //     }

        //     $workers = ProjectModel::findWorkersByProjectId(
        //         $projectId,
        //         [
        //             'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
        //             'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
        //         ]
        //     );

        //     if (!$workers) {
        //         Response::success([], 'No workers found for the specified project.');
        //     } else {
        //         $return = [];
        //         foreach ($workers as $worker) {
        //             $return[] = $worker;
        //         }
        //         Response::success($return, 'Workers fetched successfully.');
        //     }
        // } catch (Exception $e) {
        //     Response::error($e->getMessage());
        // }

    }

    // Used to fetch multiple workers by IDs or name filter (eg. /get-worker-info?ids=1,2,3 or /get-worker-info?name=John)
    public static function getProjectWorkerByKey(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $workers = [];
            // Check if 'key' parameter is present in the query string
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $workers = WorkerModel::searchProjectWorker(
                    $projectId,
                    trimOrNull($_GET['key'] ?? '') ?? ''
                );
            } else {
                $workers = WorkerModel::findProjectWorkersByProjectId(
                    $projectId,
                    [
                        'limit'     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]
                );
            }

            if (!$workers) {
                Response::success([], 'No workers found for the specified project.');
            } else {
                $return = [];
                foreach ($workers as $worker) {
                    $return[] = $worker;
                }
                Response::success($return, 'Workers fetched successfully.');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }




















    public static function getTaskWorkerById($args = []): void
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
    public static function getTaskWorkerByKey(): void
    {
        $workers = UserModel::all();

        $workerIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
        $name = $_GET['name'] ?? null;

        $offset = (int) $_GET['offset'] ?: 0;
        if ($offset > 20)
            Response::success([], 'No more workers to load');

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

                $userPerformance = WorkerPerformanceCalculator::calculate(ProjectModel::all());
                $returnDataArray[] = [
                    ...$user->toArray(),
                    'totalProjects' => count(ProjectModel::all()),
                    'completedProjects' => ProjectModel::all()->getCountByStatus(WorkStatus::COMPLETED),
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

                $userPerformance = WorkerPerformanceCalculator::calculate(ProjectModel::all());
                $returnDataArray[] = [
                    'id' => $user->getPublicId(),
                    'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                    'profilePicture' => $user->getProfileLink(),
                    'bio' => $user->getBio(),
                    'email' => $user->getEmail(),
                    'contactNumber' => $user->getContactNumber(),
                    'role' => $user->getRole()->value,
                    'jobTitles' => $user->getJobTitles()->toArray(),
                    'totalTasks' => count(ProjectModel::all()),
                    'completedTasks' => ProjectModel::all()->getCountByStatus(WorkStatus::COMPLETED),
                    'performance' => $userPerformance['overallScore'],
                ];
            }

        }

        Response::success($returnDataArray, 'Worker added successfully');
    }

    public static function edit(array $args = []): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        Response::success([], 'Worker updated successfully');
    }

    public static function terminate(): void
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