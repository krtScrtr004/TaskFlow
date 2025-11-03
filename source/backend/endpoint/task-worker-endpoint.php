<?php

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Model\TaskModel;
use App\Model\TaskWorkerModel;
use App\Model\UserModel;
use App\Utility\WorkerPerformanceCalculator;

class TaskWorkerEndpoint
{
    // private static function createResponseArrayData(Worker $worker): array
    // {
    //     $worker->setRole(Role::WORKER);
    //     $projects = ProjectModel::all();
    //     $workerPerformanceProject = WorkerPerformanceCalculator::calculate($projects);
    //     return [
    //         ...$worker->toArray(),
    //         'totalProjects' => $workerPerformanceProject['totalProjects'],
    //         'completedProjects' => $projects->getCountByStatus(WorkStatus::COMPLETED),
    //         'performance' => $workerPerformanceProject['overallScore'],
    //     ];
    // }

    public static function getById($args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }

            $workerId = isset($args['workerId'])
                ? UUID::fromString($args['workerId'])
                : null;
            if (!$workerId) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (isset($projectId) && !$project) {
                throw new NotFoundException('Project not found.');
            }

            $taskId =  isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;

            $task = TaskModel::findById($taskId);
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $worker = TaskWorkerModel::findById($workerId,  $task->getId() ?? null, $project->getId() ?? null);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            } 

            // $performance = WorkerPerformanceCalculator::calculate($worker->getAdditionalInfo('projectHistory'));
            // $worker->addAdditionalInfo('performance', $performance['overallScore']);
            Response::success([$worker], 'Worker fetched successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (NotFoundException $e) {
            Response::error('Resource Not Found.', [$e->getMessage()], 404);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    // Used to fetch multiple workers by IDs or name filter (eg. /get-worker-info?ids=1,2,3 or /get-worker-info?name=John)
    public static function getByKey(array $args = []): void
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
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (isset($projectId) && !$project) {
                throw new NotFoundException('Project not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId);
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $workers = [];
            if (isset($_GET['ids']) && trim($_GET['ids']) !== '') {
                $ids = explode(',', trimOrNull($_GET['ids'] ?? ''));
                $uuids = [];
                foreach ($ids as $id) {
                    $uuids[] = UUID::fromString($id);
                }
                $workers = TaskWorkerModel::findMultipleById($uuids, $task->getId() ?? null, $project->getId() ?? null);
            } else {
                $key = null;
                if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                    $key = trimOrNull($_GET['key'] ?? '');
                }

                $status = null;
                if (isset($_GET['status']) && trim($_GET['status']) !== '') {
                    $status = WorkerStatus::from(trimOrNull($_GET['status'] ?? ''));
                }

                $excludeTaskTerminated = false;
                if (isset($_GET['excludeTaskTerminated']) && trim($_GET['excludeTaskTerminated']) !== '') {
                    $excludeTaskTerminated = (bool) $_GET['excludeTaskTerminated'];
                    if ($excludeTaskTerminated && !isset($projectId)) {
                        throw new ForbiddenException('Project ID is required when excluding terminated task workers.');
                    }
                }

                $workers = TaskWorkerModel::search(
                    $key,
                    $task->getId() ?? null,
                    $project->getId() ?? null,
                    $status,
                    [
                        'excludeTaskTerminated'  => $excludeTaskTerminated,
                        'limit'                     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'                    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]
                );
            }
            if (!$workers) {
                Response::success([], 'No workers found for the specified task.');
            } else {
                $return = [];
                foreach ($workers as $worker) {
                    $return[] = $worker;
                }
                Response::success($return, 'Workers fetched successfully.');
            }
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    public static function add(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }
            Csrf::protect();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!isset($projectId)) {
                throw new ForbiddenException('Project ID is required.');
            }
            $project = ProjectModel::findById($projectId);
            if ($project === null) {
                throw new NotFoundException('Project not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!isset($taskId)) {
                throw new ForbiddenException('Task ID is required.');
            }
            $task = TaskModel::findById($taskId);
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            $workerIds = $data['workerIds'] ?? null;
            if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
                throw new ForbiddenException('Worker IDs are required.');
            }
            
            $ids = [];
            foreach ($workerIds as $workerId) {
                $ids[] = UUID::fromString($workerId);
            }
            TaskWorkerModel::createMultiple($task->getId(), $ids);
            
            Response::success([], 'Workers added successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    public static function edit(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!isset($projectId)) {
                throw new ForbiddenException('Project ID is required.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId']) 
                : null;
            if (!isset($taskId)) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId);
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $workerId = $args['workerId'] ?? null;
            if (!isset($workerId)) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = TaskWorkerModel::findById(
                UUID::fromString($workerId),
                null,
                ProjectModel::findById($projectId)?->getId() ?? null
            );
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            TaskWorkerModel::save([
                'taskId'        => $task->getId(),
                'workerId'      => $worker->getId(),
                'status'        => isset($data['status']) ? WorkerStatus::from($data['status']) : null,
            ]);

            Response::success([], 'Worker status updated successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }
}