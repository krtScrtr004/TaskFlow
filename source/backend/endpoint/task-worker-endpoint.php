<?php

use App\Abstract\Endpoint;
use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Enumeration\WorkerStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Model\TaskModel;
use App\Model\TaskWorkerModel;
use App\Utility\ResponseExceptionHandler;

class TaskWorkerEndpoint extends Endpoint
{
    /**
     * Retrieves a TaskWorker by its ID, with project, phase, and task context.
     *
     * This method performs the following actions:
     * - Ensures the request is a GET request and the user session is authorized.
     * - Validates and converts provided workerId, projectId, phaseId, and taskId to UUID objects.
     * - Checks for the existence of the specified project, phase, and task.
     * - Fetches the TaskWorker using the provided identifiers.
     * - Returns the worker data in a successful response, or appropriate error responses on failure.
     *
     * @param array $args Associative array of arguments with the following keys:
     *      - workerId: string|UUID Required. The unique identifier of the worker.
     *      - projectId: string|UUID Required. The unique identifier of the project.
     *      - phaseId: string|UUID Required. The unique identifier of the phase.
     *      - taskId: string|UUID Required. The unique identifier of the task.
     *
     * @return void Outputs a JSON response with the worker data or error information.
     *
     * @throws ForbiddenException If the request method is not GET, the session is unauthorized, or required IDs are missing.
     * @throws NotFoundException If the specified project, phase, task, or worker is not found.
     * @throws ValidationException If validation of input data fails.
     * @throws Exception For any other unexpected errors.
     */
    public static function getById(array $args = []): void
    {
        try {
            self::rateLimit();

            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
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
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $phaseId);
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $worker = TaskWorkerModel::findById($workerId, $task->getId() ?? null, null, $project->getId() ?? null);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            Response::success([$worker], 'Worker fetched successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Worker Fetch Failed.', $e);
        }
    }

    /**
     * Retrieves task workers by key or IDs for a specific project, phase, and task.
     *
     * This endpoint validates the request method and session authorization, then fetches workers
     * based on provided project, phase, and task identifiers. It supports searching by key, status,
     * and exclusion of terminated task workers, as well as fetching multiple workers by IDs.
     *
     * Request validation and error handling:
     * - Ensures GET request method.
     * - Checks for authorized user session.
     * - Validates presence and format of projectId, phaseId, and taskId as UUIDs.
     * - Throws exceptions for missing or invalid identifiers and not found resources.
     *
     * Worker retrieval logic:
     * - If 'ids' is provided in $_GET, fetches multiple workers by their IDs.
     * - Otherwise, supports searching by 'key', 'status', and 'excludeTaskTerminated' flags.
     * - Supports pagination via 'limit' and 'offset' query parameters.
     *
     * Response:
     * - Returns a success response with the list of workers or an empty array if none found.
     * - Handles validation, forbidden, and unexpected errors with appropriate HTTP status codes.
     *
     * @param array $args Associative array containing identifiers:
     *      - projectId: string|UUID Project identifier (required)
     *      - phaseId: string|UUID Phase identifier (optional, required if searching by phase)
     *      - taskId: string|UUID Task identifier (optional, required if searching by task)
     * 
     * Query parameters ($_GET):
     *      - ids: string Comma-separated list of worker IDs (optional)
     *      - key: string Search key for workers (optional)
     *      - status: string Worker status (optional)
     *      - excludeTaskTerminated: bool Exclude terminated task workers (optional)
     *      - limit: int Maximum number of workers to return (optional, default 10)
     *      - offset: int Offset for pagination (optional, default 0)
     * 
     * @return void Outputs JSON response with workers or error details
     */
    public static function getByKey(array $args = []): void
    {
        try {
            self::rateLimit();

            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (isset($args['phaseId']) && !$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = isset($args['phaseId'])
                ? PhaseModel::findById($phaseId)
                : null;
            if (!isset($args['phaseId']) && $phase) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (isset($args['taskId']) && !$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = isset($args['taskId'])
                ? TaskModel::findById($taskId, $phase->getId())
                : null;
            if (isset($args['taskId']) && !$task) {
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
                        throw new ForbiddenException('Project ID is required to exclude terminated task workers.');
                    }
                }

                $workers = TaskWorkerModel::search(
                    $key,
                    $task?->getId() ?? null,
                    $phase?->getId() ?? null,
                    $project?->getId() ?? null,
                    $status,
                    [
                        'excludeTaskTerminated' => $excludeTaskTerminated,
                        'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
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
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Worker Fetch Failed.', $e);
        }
    }

    /**
     * Adds multiple workers to a specific task within a project phase.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized.
     * - Protects against CSRF attacks.
     * - Decodes input data from the request.
     * - Validates the existence of projectId, phaseId, and taskId in the arguments.
     * - Ensures the referenced project, phase, and task exist.
     * - Validates that workerIds are provided and are in the correct format.
     * - Converts workerIds to UUID objects.
     * - Associates the specified workers with the given task.
     * - Returns a success response if workers are added successfully.
     * - Handles validation, forbidden access, and unexpected errors with appropriate responses.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier
     *      - phaseId: string|UUID Phase identifier
     *      - taskId: string|UUID Task identifier
     * 
     * Input data (decoded from request body) must include:
     *      - workerIds: array List of worker identifiers (string|UUID)
     *
     * @throws ValidationException If input data is invalid or cannot be decoded.
     * @throws ForbiddenException If session is unauthorized or required IDs are missing.
     * @throws NotFoundException If project or phase does not exist.
     * @throws Exception For unexpected errors.
     *
     * @return void
     */
    public static function add(array $args = []): void
    {
        try {
            self::formRateLimit();

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
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
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!isset($phaseId)) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = PhaseModel::findById($phaseId);
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!isset($taskId)) {
                throw new ForbiddenException('Task ID is required.');
            }

            $workerIds = $data['workerIds'] ?? null;
            if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
                throw new ForbiddenException('Worker IDs are required.');
            }

            $ids = [];
            foreach ($workerIds as $workerId) {
                $ids[] = UUID::fromString($workerId);
            }
            TaskWorkerModel::createMultiple($taskId, $ids);

            Response::success([], 'Workers added successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Add Worker Failed.', $e);
        }
    }

    /**
     * Edits the status of a worker assigned to a specific task within a project phase.
     *
     * This method performs the following actions:
     * - Validates user session authorization and CSRF protection.
     * - Ensures required identifiers (projectId, phaseId, taskId, workerId) are provided and valid UUIDs.
     * - Retrieves the specified task and worker, throwing exceptions if not found.
     * - Decodes input data and validates the worker status.
     * - Updates the worker's status for the given task.
     * - Returns a success response if the operation is successful, or appropriate error responses for validation, authorization, or unexpected errors.
     *
     * @param array $args Associative array containing required identifiers:
     *      - projectId: string|UUID Project identifier
     *      - phaseId: string|UUID Phase identifier
     *      - taskId: string|UUID Task identifier
     *      - workerId: string|UUID Worker identifier
     * 
     * Input data (decoded from request body):
     *      - status: string|WorkerStatus New status for the worker
     *
     * @throws ForbiddenException If session is unauthorized or required identifiers are missing
     * @throws NotFoundException If the specified task or worker is not found
     * @throws ValidationException If input data is invalid or cannot be decoded
     * @throws Exception For unexpected errors
     *
     * @return void
     */
    public static function edit(array $args = []): void
    {
        try {
            self::formRateLimit();

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $phaseId);
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
                'taskId' => $task->getId(),
                'workerId' => $worker->getId(),
                'status' => isset($data['status']) ? WorkerStatus::from($data['status']) : null,
            ]);

            Response::success([], 'Worker status updated successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Edit Worker Status Failed.', $e);
        }
    }

    /**
     * Removes a worker assignment from a task within a project phase.
     *
     * This endpoint handler performs authorization and input validation, converts ID strings
     * to UUID objects, verifies existence and relationships of the target resources, and
     * removes the worker from the specified task:
     * - Ensures an authorized session is present
     * - Enforces CSRF protection
     * - Converts projectId, phaseId, taskId, and workerId to UUID objects
     * - Verifies the project exists
     * - Verifies the phase exists
     * - Verifies the task exists and belongs to the given phase
     * - Verifies the worker exists within the given project
     * - Deletes the task-worker relation and returns a success response
     *
     * @param array $args Associative array containing request parameters with following keys:
     *      - projectId: string|UUID Project public identifier (required)
     *      - phaseId: string|UUID Phase public identifier (required)
     *      - taskId: string|UUID Task public identifier (required)
     *      - workerId: string|UUID Worker public identifier (required)
     *
     * Behavior on error:
     * - Missing or invalid IDs and unauthorized access will trigger ForbiddenException internally.
     * - Non-existent resources (project, phase, task, worker) will trigger NotFoundException internally.
     * - Any Throwable is caught and forwarded to ResponseExceptionHandler to produce an appropriate error response.
     *
     * @return void Sends a JSON success response on completion or delegates error handling to the response exception handler.
     */
    public static function delete(array $args = []): void
    {
        try {
            self::formRateLimit();

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!isset($projectId)) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $phase = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!isset($phase)) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = PhaseModel::findById($phase);
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $task = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!isset($task)) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($task, $phase->getId());
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $workerId = isset($args['workerId'])
                ? UUID::fromString($args['workerId'])
                : null;
            if (!isset($workerId)) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = ProjectWorkerModel::findById($workerId, $project->getId(), true);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            TaskWorkerModel::delete([
                'taskId' => $task->getId(),
                'workerId' => $worker->getId(),
            ]);

            Response::success([], 'Worker removed from task successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Remove Worker Failed.', $e);
        }
    }

    /**
     * Not implemented (No use case)
     */
    public static function create(array $args = []): void
    {
    }
}