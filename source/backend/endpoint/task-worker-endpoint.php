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
    /**
     * Retrieves a TaskWorker by its ID, with optional project and task context.
     *
     * This method performs the following actions:
     * - Ensures the request is a GET request and the user session is authorized.
     * - Validates and converts provided workerId, projectId, and taskId to UUID objects.
     * - Checks for the existence of the specified project and task.
     * - Fetches the TaskWorker using the provided identifiers.
     * - Returns the worker data in a successful response, or appropriate error responses on failure.
     *
     * @param array $args Associative array of arguments with the following keys:
     *      - workerId: string|UUID Required. The unique identifier of the worker.
     *      - projectId: string|UUID|null Optional. The unique identifier of the project.
     *      - taskId: string|UUID|null Optional. The unique identifier of the task.
     *
     * @return void Outputs a JSON response with the worker data or error information.
     *
     * @throws ForbiddenException If the request method is not GET, the session is unauthorized, or required IDs are missing.
     * @throws NotFoundException If the specified project, task, or worker is not found.
     * @throws ValidationException If validation of input data fails.
     * @throws Exception For any other unexpected errors.
     */
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

    /**
     * Retrieves task workers based on provided criteria.
     *
     * This method handles GET requests to fetch task workers associated with a specific task and project.
     * It performs authentication and authorization checks, validates input parameters, and supports
     * searching by worker IDs, key, status, and additional filters.
     *
     * The following logic is applied:
     * - Ensures the request method is GET.
     * - Checks for an authorized user session.
     * - Validates and converts projectId and taskId to UUID objects.
     * - Fetches the corresponding Project and Task models.
     * - If 'ids' are provided in the query, fetches multiple workers by their IDs.
     * - Otherwise, supports searching by 'key', 'status', and 'excludeTaskTerminated' flag.
     * - Supports pagination via 'limit' and 'offset' query parameters.
     * - Returns a success response with the list of workers or an empty array if none found.
     * - Handles validation, forbidden access, and unexpected errors with appropriate responses.
     *
     * @param array $args Associative array of arguments, including:
     *      - projectId: string|UUID|null Project identifier (optional, required for some filters)
     *      - taskId: string|UUID Task identifier (required)
     *
     * Query parameters supported (via $_GET):
     *      - ids: string Comma-separated list of worker IDs to fetch
     *      - key: string Search key for filtering workers
     *      - status: string Worker status filter
     *      - excludeTaskTerminated: bool Exclude workers from terminated tasks (requires projectId)
     *      - limit: int Maximum number of workers to return (default: 10)
     *      - offset: int Offset for pagination (default: 0)
     *
     * @return void Outputs a JSON response with the list of workers or error information.
     *
     * @throws ValidationException If input validation fails.
     * @throws ForbiddenException If the request is not allowed or session is unauthorized.
     * @throws NotFoundException If the specified project or task is not found.
     * @throws Exception For any other unexpected errors.
     */
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

    /**
     * Adds multiple workers to a specific task within a project.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized.
     * - Validates CSRF token.
     * - Decodes input data from the request body.
     * - Validates and retrieves the project and task by their IDs.
     * - Validates the list of worker IDs to be added.
     * - Converts worker IDs to UUID objects.
     * - Associates the specified workers with the given task.
     * - Returns a success response if all operations succeed.
     * - Handles and returns appropriate error responses for validation, authorization, and unexpected errors.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier (required)
     *      - taskId: string|UUID Task identifier (required)
     *
     * Input JSON body should contain:
     *      - workerIds: array List of worker IDs (string or UUID) to be added to the task (required)
     *
     * @throws ValidationException If input data is invalid or cannot be decoded.
     * @throws ForbiddenException If session is unauthorized, project/task/worker IDs are missing, or CSRF fails.
     * @throws NotFoundException If the specified project or task does not exist.
     * @throws Exception For any other unexpected errors.
     *
     * @return void
     */
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

    /**
     * Edits the status of a worker assigned to a specific task within a project.
     *
     * This method performs the following actions:
     * - Validates the user's session and CSRF token.
     * - Ensures required parameters (projectId, taskId, workerId) are present and valid UUIDs.
     * - Checks if the specified task and worker exist within the given project.
     * - Decodes the input data and updates the worker's status for the task.
     * - Handles and responds to validation, authorization, and unexpected errors.
     *
     * @param array $args Associative array containing the following keys:
     *      - projectId: string|UUID Project identifier (required)
     *      - taskId: string|UUID Task identifier (required)
     *      - workerId: string|UUID Worker identifier (required)
     *
     * Input Data (from php://input):
     *      - status: string|WorkerStatus New status for the worker (optional)
     *
     * @throws ForbiddenException If the user is not authorized or required parameters are missing.
     * @throws NotFoundException If the specified task or worker does not exist.
     * @throws ValidationException If the input data cannot be decoded or is invalid.
     * @throws Exception For any other unexpected errors.
     *
     * @return void Responds with a success message or error details.
     */
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