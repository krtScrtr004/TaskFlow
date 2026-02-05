<?php

namespace App\Endpoint;

use App\Abstract\Endpoint;
use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\WorkerContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Entity\TaskWorker;
use App\Entity\Task;
use App\Enumeration\Role;
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
use App\Service\TaskService;
use App\Utility\ResponseExceptionHandler;
use App\Utility\TemporaryId;
use App\Validator\ResourceValidator;
use App\Validator\UserValidator;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use Throwable;
use ValueError;

class TaskEndpoint extends Endpoint
{
    private ProjectModel $projectModel;
    private ProjectWorkerModel $projectWorkerModel;
    private PhaseModel $phaseModel;
    private TaskModel $taskModel;

    private function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->projectWorkerModel = new ProjectWorkerModel();
        $this->phaseModel = new PhaseModel();
        $this->taskModel = new TaskModel();
    }

    /**
     * Retrieves a task by its ID within a specific project and phase.
     *
     * This method performs the following actions:
     * - Validates that the request method is GET.
     * - Checks if the user session is authorized.
     * - Validates and converts projectId, phaseId, and taskId to UUID objects.
     * - Ensures the project, phase, and task exist in the database.
     * - Returns a success response if the task is found, or appropriate error responses otherwise.
     *
     * @param array $args Associative array containing identifiers:
     *      - projectId: string|UUID Project identifier (required)
     *      - phaseId: string|UUID Phase identifier (required)
     *      - taskId: string|UUID Task identifier (required)
     *
     * @throws ForbiddenException If the request method is not GET, session is unauthorized, or required IDs are missing.
     * @throws NotFoundException If the project, phase, or task does not exist.
     * @throws ValidationException If validation fails.
     * @throws Exception For unexpected errors.
     *
     * @return void
     */
    public static function getById(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) throw new ForbiddenException('Invalid HTTP request method');
            if (!SessionAuth::hasAuthorizedSession()) throw new ForbiddenException();

            self::rateLimit();

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId)
                throw new ForbiddenException('Project ID is required');
            elseif ($instance->projectModel->findById($projectId) === null)
                throw new NotFoundException('Project not found');

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (isset($phaseId) && !$phaseId) throw new ForbiddenException('Phase ID is require');

            $phase = $instance->phaseModel->findById($phaseId);
            if (!$phase) throw new NotFoundException('Phase not found');

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) throw new ForbiddenException('Task ID is required');

            $task = $instance->taskModel->findById($taskId, $phaseId, $projectId);
            if (!$task) throw new NotFoundException('Task not found');

            Response::success([], 'Task fetched successfully');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Get Task Failed', $e);
        }
    }

    /**
     * Retrieves tasks based on provided query parameters and project context.
     *
     * This method handles GET requests to fetch tasks using various filters:
     * - If 'key' is present in the query string, performs a search for tasks matching the key within the specified project.
     * - If 'status' is present, retrieves tasks by their status within the specified project, supporting pagination.
     * - If neither 'key' nor 'status' is provided, fetches all tasks for the project or all tasks globally, supporting pagination.
    /**
     * Retrieves tasks based on provided query parameters within a phase context.
     *
     * This method handles GET requests to fetch tasks using various filters:
     * - If 'key' is present in the query string, performs a search for tasks matching the key within the specified phase.
     * - If 'status' is present, retrieves tasks by their status within the specified phase, supporting pagination.
     * - If neither 'key' nor 'status' is provided, fetches all tasks for the phase, supporting pagination.
     * - Requires a valid session and GET request method.
     *
     * @param array $args Optional arguments for task retrieval:
     *      - projectId: string|UUID Project identifier
     *      - phaseId: string|UUID Phase identifier
     *
     * Query Parameters (via $_GET):
     *      - key: string (optional) Search keyword for tasks
     *      - status: string (optional) Status to filter tasks (must be a valid WorkStatus)
     *      - limit: int (optional) Maximum number of tasks to return (default: 10)
     *      - offset: int (optional) Number of tasks to skip for pagination (default: 0)
     *
     * @throws ForbiddenException If the request method is not GET, session is unauthorized, or IDs are invalid
     * @throws ValidationException If validation of parameters fails
     * @throws Exception For any other unexpected errors
     *
     * Responds with:
     *      - 200: Success, with an array of tasks or an empty array if none found
     *      - 403: Forbidden, if authentication or authorization fails
     *      - 422: Validation failed, with error details
     *      - 500: Unexpected server error
     */
    public static function getByKey(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) throw new ForbiddenException('Invalid HTTP request method');
            if (!SessionAuth::hasAuthorizedSession()) throw new ForbiddenException();

            self::rateLimit();

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) throw new ForbiddenException('Project ID is required');

            $project = $instance->projectModel->findById($projectId);
            if (!$project) throw new NotFoundException('Project not found');

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (isset($args['phaseId']) && !$phaseId) throw new ForbiddenException('Phase ID is required');

            $phase = isset($args['phaseId'])
                ? $instance->phaseModel->findById($phaseId)
                : null;
            if (isset($args['phaseId']) && !$phase)
                throw new NotFoundException('Phase not found');

            $workerId = isset($args['workerId'])
                ? UUID::fromString($args['workerId'])
                : null;
            if (isset($args['workerId']) && !$workerId)
                throw new ForbiddenException('Worker ID is required');

            /** @var TaskWorker */
            $worker = isset($args['workerId'])
                ? $instance->projectWorkerModel->findById($workerId)
                : null;
            if (isset($args['workerId']) && !$worker)
                throw new NotFoundException('Worker not found');

            // Check if 'key' parameter is present in the query string
            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '')
                $key = trim($_GET['key']);

            // Obtain filter from query parameters
            $status = isset($_GET['status'])
                ? WorkStatus::from($_GET['status'])
                : null;
            $priority = isset($_GET['priority'])
                ? Priority::from($_GET['priority'])
                : null;

            $tasks = $instance->taskModel->search(
                $key,
                [
                    'userId'    => TemporaryId::isTemporary($worker?->getId()) 
                        ? $worker?->getId() 
                        : $worker?->getPublicId(),
                    'phaseId'   => $phase?->getId() ?? null,
                    'projectId' => $project->getId(),
                    'status'    => $status,
                    'priority'  => $priority,

                    'limit'     => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
                    'offset'    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                ]
            );

            if (!$tasks) {
                Response::success([], 'No tasks found for the specified phase');
            } else {
                $return = [];
                foreach ($tasks as $task) {
                    $return[] = $task;
                }
                Response::success($return, 'Tasks fetched successfully');
            }
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Get Task Failed', $e);
        }
    }

    /**
     * Adds a new Task to a Project Phase.
     *
     * Handles request-level concerns (rate limiting, session authorization, CSRF protection),
     * authorizes that the current user is a Project Manager, decodes and validates input data,
     * resolves the target Project and Phase (by explicit ID or schedule boundary), validates
     * task date/budget boundaries against the Phase, constructs Worker and Task domain objects,
     * performs domain validation, persists the Task via TaskService, and returns a success response
     * containing the created Task's public identifier.
     *
     * Behavior and side effects:
     * - Enforces rate limiting via self::formRateLimit().
     * - Ensures an authorized session exists and CSRF protection passes.
     * - Requires the current user to have a Project Manager role; otherwise throws ForbiddenException.
     * - Decodes input payload (php://input) and throws ValidationException if decoding fails.
     * - Extracts and validates 'projectId' and 'phaseId' from $args; missing IDs result in ForbiddenException.
     * - Loads Project and Phase instances; missing resources result in NotFoundException.
     * - If phaseId is not provided, resolves Phase by schedule boundary using start/completion date-times.
     * - Validates task start/completion date-times against the Phase bounds.
     * - Aggregates worker cost contributions into a budget boundary validator while converting worker data
     *   (renaming 'id' => 'publicId') and creating TaskWorker partial objects stored in a WorkerContainer.
     * - Normalizes priority and converts provided date/time strings into DateTime objects.
     * - Constructs a partial Task, associates it with the resolved Phase via `add`itional info,
     *   and runs full validation; validation failures throw ValidationException with collected errors.
     * - Persists the Task through TaskService::create() and emits a success Response with the new Task public ID.
     * - Catches any Throwable and delegates handling to ResponseExceptionHandler.
     *
     * @param array $args Optional routing/context arguments; expects 'projectId' and optionally 'phaseId' as UUID strings.
     *
     * @throws ForbiddenException If the session is unauthorized, the user lacks Project Manager role,
     *                            or required project/phase IDs are missing.
     * @throws ValidationException If request decoding fails or task/domain validation fails.
     * @throws NotFoundException If the referenced Project or Phase cannot be found.
     * @throws Throwable For any other unexpected errors surfaced during processing (caught and handled by the caller).
     *
     * @return void
     */
    public static function add(array $args = []): void
    {
        try {
            if (!HttpAuth::isPOSTRequest()) throw new ForbiddenException('Invalid HTTP request method');
            if (!SessionAuth::hasAuthorizedSession()) throw new ForbiddenException();

            self::formRateLimit();
            Csrf::protect();

            $instance = new self();

            if (!Role::isProjectManager(Me::getInstance()->getRole()))
                throw new ForbiddenException('Only project managers are allowed to add tasks');

            $data = decodeData('php://input');
            if (!$data) throw new ValidationException('Cannot decode data');

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) throw new ForbiddenException('Project ID is required');

            $project = $instance->projectModel->findById($projectId);
            if (!$project) throw new NotFoundException('Project not found');

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) throw new ForbiddenException('Phase ID is required');

            // Search for phase by ID or by schedule boundary
            $phase = isset($args['phaseId'])
                ? $instance->phaseModel->findById($phaseId)
                : $instance->phaseModel->findByScheduleBoundary(
                    $project->getId(),
                    $data['startDateTime'] ?? null,
                    $data['completionDateTime'] ?? null
                );
            if (!$phase) throw new NotFoundException('Phase not found');

            $validator = new WorkValidator();
            $validator->validateDateBounds(
                new DateTime($data['startDateTime']),
                new DateTime($data['completionDateTime']),
                $phase->getStartDateTime(),
                $phase->getCompletionDateTime(),
                'Phase'
            );

            $budgetBoundaryValidator = $validator->createBudgetBoundaryValidator((float) $data['estimatedCost'] ?? DEFAULT_RATE_MIN);

            $rawWorkers = $data['workers'];
            $workers = new WorkerContainer();
            foreach ($rawWorkers as $worker) {
                $worker['publicId'] = $worker['id'];
                unset($worker['id']);

                // Add to budget boundary validator
                $budgetBoundaryValidator['addBudget']((float) $worker['unitRate'] * (float) $worker['estimatedHour']);

                $workers->add(TaskWorker::createPartial($worker));
            }

            $data['workers'] = $workers;
            $data['priority'] = Priority::from($data['priority'] ?? Priority::LOW);
            $data['startDateTime'] = new DateTime($data['startDateTime']);
            $data['completionDateTime'] = new DateTime($data['completionDateTime']);

            $task = Task::createPartial($data);
            $task->addAdditionalInfo('phaseId', $phase->getId());

            $validator->validateMultiple($data);
            if ($validator->hasErrors())
                throw new ValidationException(
                    'Task Validation Failed',
                    $validator->getErrors()
                );

            $newTask = TaskService::create($task);
            Response::success(
                ['id' => UUID::toString($newTask->getPublicId())],
                'Workers added successfully'
            );
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Add Task Failed', $e);
        }
    }

    /**
     * Edits an existing Task along with its associated TaskWorkers and Resources.
     *
     * Handles request-level concerns (rate limiting, session authorization, CSRF protection),
     * authorizes field-specific edits based on user role, decodes and validates input data,
     * resolves the target Project, Phase, and Task, validates task date/budget boundaries against the Phase,
     * processes updates to Task fields, Workers, and Resources, performs domain validation,
     * persists the changes via TaskService, and returns a success response.
     *
     * Behavior and side effects:
     * - Enforces rate limiting via self::formRateLimit().
     * - Ensures an authorized session exists and CSRF protection passes.
     * - Authorizes edits to specific fields based on whether the user is a Project Manager; unauthorized edits throw ForbiddenException.
     * - Decodes input payload (php://input) and throws ValidationException if decoding fails.
     * - Extracts and validates 'projectId', 'phaseId', and 'taskId' from $args; missing IDs result in ForbiddenException.
     * - Loads Project, Phase, and Task instances; missing resources result in NotFoundException.
     * - If phaseId is not provided, resolves Phase by schedule boundary using start/completion date-times.
     * - Validates updated task start/completion date-times against the Phase bounds.
     * - Aggregates worker and resource cost contributions into a budget boundary validator while converting their data
     *   (renaming 'id' => 'publicId').
     * - Constructs a partial Task with updated fields and runs full validation; validation failures throw ValidationException with collected errors.
     * - Persists the changes through TaskService::save() and emits a success Response with the Project public ID.
     * - Catches any Throwable and delegates handling to ResponseExceptionHandler.
     *
     * @param array $args Optional routing/context arguments; expects 'projectId', 'phaseId', and 'taskId' as UUID strings.
     *
     * @throws ForbiddenException If the session is unauthorized, unauthorized field edits are attempted,
     *                            or required project/phase/task IDs are missing.
     * @throws ValidationException If request decoding fails or task/domain validation fails.
     * @throws NotFoundException If the referenced Project, Phase, or Task cannot be found.
     * @throws Throwable For any other unexpected errors surfaced during processing (caught and handled by the
     * caller).
     * @return void
     */
    public static function edit(array $args = []): void
    {
        try {
            if (!HttpAuth::isPATCHRequest() && !HttpAuth::isPUTRequest()) 
                throw new ForbiddenException('Invalid HTTP request method');
            if (!SessionAuth::hasAuthorizedSession()) throw new ForbiddenException();

            self::formRateLimit();
            Csrf::protect();

            $instance = new self();

            $projectManagerOnly = ['name', 'description', 'priority', 'startDateTime', 'completionDateTime'];
            foreach ($projectManagerOnly as $field) {
                if (isset($data[$field]) && !Role::isProjectManager(Me::getInstance()->getRole()))
                    throw new ForbiddenException("Only Project Managers are allowed to edit {$field} field");
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId)
                throw new ForbiddenException('Project ID is required');
            elseif (!$instance->projectModel->findById($projectId))
                throw new NotFoundException('Project not found');

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) throw new ForbiddenException('Phase ID is required');

            $phase = isset($args['phaseId'])
                ? $instance->phaseModel->findById($phaseId)
                : $instance->phaseModel->findOnGoingByProjectId($projectId);
            if (!$phase) throw new NotFoundException('Phase not found');

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) throw new ForbiddenException('Task ID is required');

            $task = $instance->taskModel->findById($taskId, $phaseId);
            if (!$task) throw new NotFoundException('Task not found');

            $project = ($projectId)
                ? $instance->projectModel->findById($projectId)
                : $instance->taskModel->findOwningProject($task->getId());

            $data = decodeData('php://input');
            if (!$data) throw new ValidationException('Cannot decode data');

            $validator = new WorkValidator();

            $taskData = ['id' => $task->getId()];

            // Validate Task fields
            if (isset($data['name'])) $taskData['name'] = $data['name'];

            if (isset($data['description'])) $taskData['description'] = trimOrNull($data['description']);

            if (isset($data['startDateTime'])) $taskData['startDateTime'] = new DateTime($data['startDateTime']);

            if (isset($data['completionDateTime'])) $taskData['completionDateTime'] = new DateTime($data['completionDateTime']);

            if (isset($data['priority'])) $taskData['priority'] = Priority::from($data['priority']);

            if (isset($data['estimatedCost'])) $taskData['estimatedCost'] = (float) $data['estimatedCost'];

            if (isset($data['budgetNote'])) $taskData['budgetNote'] = trimOrNull($data['budgetNote']);

            if (isset($data['status'])) {
                $taskData['status'] = WorkStatus::from($data['status']);
            } elseif ($task->getStatus() !== WorkStatus::DELAYED) {
                $taskData['status'] = WorkStatus::getStatusFromDates(
                    $taskData['startDateTime'] ?? $task->getStartDateTime(),
                    $taskData['completionDateTime'] ?? $task->getCompletionDateTime()
                );
            }

            $budgetBoundaryValidator = $validator->createBudgetBoundaryValidator((float) $data['estimatedCost'] ?: $task->getEstimatedCost());

            // Validate and prepare Task Workers
            $userValidator = new UserValidator();
            $workerCategories = $data['workers'] ?? [];
            if (!is_array($workerCategories)) throw new ValidationException('Invalid workers data format provided');

            foreach ($workerCategories as $category => &$workers) {
                foreach ($workers as &$worker) {
                    $worker['workerId'] = UUID::fromString($worker['id']);
                    unset($worker['id']);

                    if ($category === 'toRemove') {
                        // Subtract budget for removed workers
                        $budgetBoundaryValidator['subtractBudget']((float) $worker['unitRate'] * (float) $worker['estimatedHour']);
                        unset($worker['unitRate'], $worker['estimatedHour']); // Remove unnecessary fields for removal

                        $worker['status'] = WorkerStatus::TERMINATED;
                    } elseif ($category === 'toAdd' || $category === 'toEdit') {
                        // Add to budget for added/edited workers
                        $budgetBoundaryValidator['addBudget']((float) $worker['unitRate'] * (float) $worker['estimatedHour']);
                        $userValidator->validateMultiple($worker);
                    }

                    if ($category === 'toEdit' || $category === 'toRemove') $worker['taskId'] = $task->getId();

                    $taskData['workers'][$category][] = $worker;
                }
            }

            $resourceValidator = new ResourceValidator();
            $resources = $data['resources'] ?? [];
            if (!is_array($resources)) throw new ValidationException('Invalid resources data format provided.');

            foreach ($resources as $resource) {
                $budgetBoundaryValidator['addBudget']((float) $resource['unitRate'] * (float) $resource['estimatedUnit']);

                $resource['publicId'] = $resource['id'];
                unset($resource['id']);

                $resourceValidator->validateMultiple($resource);
                $taskData['resources'][] = $resource;
            }

            if ($taskData && count($taskData) > 1) {
                // Validate date bounds if dates are being updated
                $validator->validateDateBounds(
                    $taskData['startDateTime'] ?? $task->getStartDateTime(),
                    $taskData['completionDateTime'] ?? $task->getCompletionDateTime(),
                    $phase->getStartDateTime(),
                    $phase->getCompletionDateTime(),
                    'Phase'
                );

                $mergedErrors = array_merge(
                    $validator->getErrors() ??  [],
                    $userValidator->getErrors() ?? [],
                    $resourceValidator->getErrors() ?? []
                );
                if ($mergedErrors && count($mergedErrors) > 0) {
                    throw new ValidationException('Task Validation Failed', $mergedErrors);
                }

                TaskService::save($taskData);
            }

            Response::success(
                ['projectId' => UUID::toString($project->getPublicId())],
                'Project edited successfully'
            );
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Edit Task Failed', $e);
        }
    }

    /**
     * Not implemented (No use case)
     */
    public static function create(array $args = []): void {}

    /**
     * Not implemented (No use case)
     */
    public static function delete(array $args = []): void {}
}
