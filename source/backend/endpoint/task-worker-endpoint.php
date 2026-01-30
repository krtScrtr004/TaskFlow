<?php

use App\Abstract\Endpoint;
use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Entity\Resource;
use App\Entity\TaskWorker;
use App\Entity\ResourceType;
use App\Entity\Task;
use App\Enumeration\ResourceTypeMapping;
use App\Enumeration\Priority;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
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
use App\Service\TaskService;
use App\Utility\ResponseExceptionHandler;
use App\Validator\ResourceValidator;
use App\Validator\UserValidator;
use App\Validator\WorkValidator;

class TaskWorkerEndpoint extends Endpoint
{
    private ProjectModel $projectModel;
    private ProjectWorkerModel $projectWorkerModel;
    private PhaseModel $phaseModel;
    private TaskModel $taskModel;
    private TaskWorkerModel $taskWorkerModel;

    private function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->projectWorkerModel = new ProjectWorkerModel();
        $this->phaseModel = new PhaseModel();
        $this->taskModel = new TaskModel();
        $this->taskWorkerModel = new TaskWorkerModel();
    }

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
            $instance = new self();

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

            $project = $instance->projectModel->findById($projectId);
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

            $task = $instance->taskModel->findById($taskId, $phaseId);
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $worker = $instance->taskWorkerModel->findById($workerId, $task->getId() ?? null, null, $project->getId() ?? null);
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
            $instance = new self();

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

            $project = $instance->projectModel->findById($projectId);
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
                ? $instance->phaseModel->findById($phaseId)
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
                ? $instance->taskModel->findById($taskId, $phase->getId())
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
                $workers = $instance->taskWorkerModel->findMultipleById($uuids, $task->getId() ?? null, $project->getId() ?? null);
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

                $workers = $instance->taskWorkerModel->search(
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
     * Updates the status of a worker assigned to a task within a project phase.
     *
     * This endpoint handler performs authorization and input validation, converts ID strings
     * to UUID objects, verifies existence and relationships of the target resources, and
     * updates the worker's status for the specified task:
     * - Ensures an authorized session is present
     * - Enforces CSRF protection
     * - Converts projectId, phaseId, taskId, and workerId to UUID objects
     * - Verifies the existence of the project, phase, task, and worker
     * - Updates the worker's status using provided data
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
    public static function add(array $args = []): void
    {
        try {
            self::formRateLimit();
            $instance = new self();

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

            $project = $instance->projectModel->findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!isset($phaseId)) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = $instance->phaseModel->findById($phaseId);
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!isset($taskId)) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = $instance->taskModel->findById($taskId, $phase->getId());
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $workValidator = new WorkValidator();
            $budgetBoundaryValidator = $workValidator->createBudgetBoundaryValidator($task->getEstimatedCost());

            $workers = new WorkerContainer();
            foreach ($data as $worker) {
                $worker['publicId'] = $worker['id'];
                unset($worker['id']);

                // Add to budget boundary validator
                $budgetBoundaryValidator['addBudget']((float) $worker['unitRate'] * (float) $worker['estimatedHour']);

                $workers->add(TaskWorker::createPartial($worker));
            }
            $task->setWorkers($workers);

            if ($workValidator->hasErrors()) {
                throw new ValidationException('Worker Validation Failed.', $workValidator->getErrors());
            }

            TaskService::create($task, ['worker' => true]);

            Response::success([], 'Workers added successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Add Worker Failed.', $e);
        }
    }

    /**
     * Updates a task worker's assignment details and status.
     *
     * This method handles the modification of a worker's role within a task, including updating
     * their status, unit rate, and estimated hours. It enforces authorization checks, validates
     * input data, and ensures budget constraints are maintained throughout the update process.
     *
     * Behavior and side effects:
     * - Validates the user has an authorized session and passes CSRF protection checks.
     * - Verifies the provided project ID, phase ID, and task ID are valid UUIDs and retrieves
     *   the corresponding task from the database.
     * - Retrieves and validates the worker exists within the specified project.
     * - Decodes JSON input data from the request body.
     * - Converts status string to WorkerStatus enum if provided.
     * - Parses unitRate and estimatedHour as floats if provided.
     * - Creates a budget boundary validator based on the task's estimated cost.
     * - If the worker status is being set to TERMINATED, subtracts the worker's existing cost
     *   from the budget boundary; otherwise, adds the updated worker cost to the boundary.
     * - Validates unitRate and estimatedHour using ResourceValidator.
     * - Persists the updated worker assignment to the database via TaskWorkerModel::save().
     * - Returns a success response on completion.
     * - Catches and handles all exceptions via ResponseExceptionHandler.
     *
     * @param array $args Associative array containing:
     *                    - 'projectId' (string): UUID of the project
     *                    - 'phaseId' (string): UUID of the phase
     *                    - 'taskId' (string): UUID of the task
     *                    - 'workerId' (string): UUID of the worker to update
     *
     * @throws ForbiddenException If the user lacks authorization, CSRF token is invalid,
     *                            or any required ID parameter is missing or invalid
     * @throws NotFoundException If the task or worker cannot be found
     * @throws ValidationException If input data cannot be decoded or validation fails
     *
     * @return void
     */
    public static function edit(array $args = []): void
    {
        try {
            self::formRateLimit();
            $instance = new self();

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

            $task = $instance->taskModel->findById($taskId, $phaseId);
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $workerId = $args['workerId'] ?? null;
            if (!isset($workerId)) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = $instance->taskWorkerModel->findById(
                UUID::fromString($workerId),
                null,
                null,
                $instance->projectModel->findById($projectId)?->getId() ?? null
            );
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $status = isset($data['status'])
                ? WorkerStatus::from($data['status'])
                : null;
            $unitRate = isset($data['unitRate'])
                ? (float) $data['unitRate']
                : null;
            $estimatedHour = isset($data['estimatedHour'])
                ? (float) $data['estimatedHour']
                : null;

            // Validate budget boundaries
            $workValidator = new WorkValidator();
            $budgetBoundaryValidator = $workValidator->createBudgetBoundaryValidator($task->getEstimatedCost());
            if ($status && $status === WorkerStatus::TERMINATED) {
                // Subtract existing worker cost from budget boundary
                $budgetBoundaryValidator['subtractBudget'](
                    (float) $worker->getUnitRate() * (float) $worker->getEstimatedHour()
                );
            } else {
                // Add updated worker cost to budget boundary
                $budgetBoundaryValidator['addBudget'](
                    (float) $unitRate * (float) $estimatedHour
                );
            }

            $resourceValidator = new ResourceValidator();
            $resourceValidator->validateMultiple([
                'unitRate'      => $unitRate,
                'hoursAssigned' => $estimatedHour
            ]);
            if ($resourceValidator->hasErrors()) {
                throw new ValidationException('Worker Validation Failed.', $resourceValidator->getErrors());
            }

            $instance->taskWorkerModel->save([
                'taskId'        => $task->getId(),
                'workerId'      => $worker->getId(),
                'status'        => $status,
                'unitRate'      => $unitRate,
                'estimatedHour' => $estimatedHour
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
            $instance = new self();

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

            $project = $instance->projectModel->findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $phase = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!isset($phase)) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = $instance->phaseModel->findById($phase);
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $task = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!isset($task)) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = $instance->taskModel->findById($task, $phase->getId());
            if (!$task) {
                throw new NotFoundException('Task not found.');
            }

            $workerId = isset($args['workerId'])
                ? UUID::fromString($args['workerId'])
                : null;
            if (!isset($workerId)) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = $instance->projectWorkerModel->findById($workerId, $project->getId(), true);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            $instance->taskWorkerModel->delete([
                'taskId'    => $task->getId(),
                'workerId'  => $worker->getId(),
            ]);

            Response::success([], 'Worker removed from task successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Remove Worker Failed.', $e);
        }
    }

    /**
     * Not implemented (No use case)
     */
    public static function create(array $args = []): void {}
}
