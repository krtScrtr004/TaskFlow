<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\TaskContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Entity\Task;
use App\Enumeration\Role;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use ValueError;

class TaskEndpoint
{

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
            } elseif (ProjectModel::findById($projectId) === null) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (isset($phaseId) && !$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = PhaseModel::findById($phaseId);
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $phaseId, $projectId);
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            Response::success([], 'Task fetched successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.', $e->getErrors(), 422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (NotFoundException $e) {
            Response::error('Resource Not Found.', [$e->getMessage()], 404);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
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
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
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

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            // Check if 'key' parameter is present in the query string
            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $key = trim($_GET['key']);
            }

            // Obtain filter from query parameters (one filter type only)
            $filter = null;
            if (isset($_GET['filter']) && strcasecmp($_GET['filter'], 'all') !== 0) {
                $filterValue = $_GET['filter'];
                // Try to parse as WorkStatus first, then TaskPriority if later fails
                try {
                    $filter = WorkStatus::from($filterValue);
                } catch (ValueError $e) {
                    $filter = TaskPriority::from($filterValue);
                }
            }

            $options = [
                'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 50,
            ];

            $tasks = TaskModel::search(
                $key, 
                Me::getInstance()->getId(), 
                $phaseId, 
                $projectId,
                $filter, 
                $options
            );
    
            if (!$tasks) {
                Response::success([], 'No tasks found for the specified phase.');
            } else {
                $return = [];
                foreach ($tasks as $task) {
                    $return[] = $task;
                }
                Response::success($return, 'Tasks fetched successfully.');
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
     * Adds a new task to a specified project phase.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized.
     * - Validates CSRF token.
     * - Decodes input data from the request body.
     * - Validates the presence and existence of project and phase IDs.
     * - Ensures the phase belongs to the specified project or finds the ongoing phase.
     * - Validates task date bounds against project dates.
     * - Creates a partial Task instance with provided data.
     * - Validates and adds worker(s) to the task.
     * - Associates the task with the specified phase.
     * - Persists the task in the database.
     * - Returns the public ID of the created task on success.
     * - Handles validation, authorization, and unexpected errors.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier (required)
     *      - phaseId: string|UUID Phase identifier (required)
     * 
     * Input data (decoded from request body) must include:
     *      - name: string Task name
     *      - description: string Task description
     *      - startDateTime: string Task start date/time (ISO 8601)
     *      - completionDateTime: string Task completion date/time (ISO 8601)
     *      - priority: int Task priority
     *      - workerIds: array List of worker public IDs (at least one required)
     * 
     * @throws ForbiddenException If session is unauthorized, project/phase/worker IDs are missing or invalid.
     * @throws ValidationException If input data is invalid or fails validation.
     * @throws NotFoundException If project or phase is not found.
     * @throws Exception For unexpected errors.
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

            // Search for phase by ID or by schedule boundary
            $phase = isset($args['phaseId'])
                ? PhaseModel::findById($phaseId)
                : PhaseModel::findByScheduleBoundary(
                    $project->getId(), 
                    $data['startDateTime'] ?? null, 
                    $data['completionDateTime'] ?? null
                );
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $validator = new WorkValidator();
            $validator->validateDateBounds(
                new DateTime($data['startDateTime']),
                new DateTime($data['completionDateTime']),
                $phase->getStartDateTime(),
                $phase->getCompletionDateTime(),
                'Phase'
            );
            if ($validator->hasErrors()) {
                throw new ValidationException('Task Validation Failed.', $validator->getErrors());
            }

            $task = Task::createPartial([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'startDateTime' => $data['startDateTime'],
                'completionDateTime' => $data['completionDateTime'],
                'priority' => $data['priority'],
            ]);

            $workerIds = $data['workerIds'] ?? null;
            if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
                throw new ForbiddenException('Worker IDs are required.');
            }
            
            foreach ($workerIds as $workerId) {
                $task->addWorker(Worker::createPartial([
                    'publicId' => UUID::fromString($workerId)
                ]));
            }
            $task->addAdditionalInfo('phaseId', $phaseId);

            $createdTask = TaskModel::create($task);
            $publicId = UUID::toString($createdTask->getPublicId());
            Response::success(['id' => $publicId], 'Workers added successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    /**
     * Edits an existing task within a project phase.
     *
     * This method performs the following operations:
     * - Validates user session authorization and CSRF protection.
     * - Validates and retrieves project, phase, and task identifiers.
     * - Loads the corresponding phase, task, and project models.
     * - Decodes input data and prepares task update payload.
     * - Converts and validates fields such as name, description, start/completion dates, priority, and status.
     * - Automatically determines status from dates if not provided.
     * - Validates date bounds against project limits.
     * - Saves the updated task if validation passes.
     * - Returns a success response with the project public ID, or error responses for validation, not found, forbidden, or unexpected errors.
     *
     * @param array $args Associative array containing identifiers:
     *      - projectId: string|UUID Project identifier
     *      - phaseId: string|UUID Phase identifier
     *      - taskId: string|UUID Task identifier
     * 
     * Input data (decoded from request body) may include:
     *      - name: string Task name
     *      - description: string Task description
     *      - startDateTime: string|DateTime Task start date/time
     *      - completionDateTime: string|DateTime Task completion date/time
     *      - priority: string|TaskPriority Task priority
     *      - status: string|WorkStatus Task status
     *
     * @throws ValidationException If input validation fails
     * @throws NotFoundException If project, phase, or task is not found
     * @throws ForbiddenException If user session is unauthorized
     * @throws Exception For unexpected errors
     *
     * @return void
     */
    public static function edit(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not allowed to edit tasks.');
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ValidationException('Project ID is required to edit a task.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) {
                throw new ValidationException('Phase ID is required to edit a task.');
            }

            $phase = isset($args['phaseId'])
                ? PhaseModel::findById($phaseId)
                : PhaseModel::findOnGoingByProjectId($projectId);
            if (!$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ValidationException('Task ID is required to edit a task.');
            }

            $task = TaskModel::findById($taskId, $phaseId);
            if (!$task) {
                throw new NotFoundException('Task is not found.');
            }

            $project = null;
            if ($projectId) {
                $project = ProjectModel::findById($projectId);
            } else {
                $project = TaskModel::findOwningProject($task->getId());
            }


            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $validator = new WorkValidator();

            $taskData = ['id' => $task->getId()];

            if (isset($data['name'])) {
                $taskData['name'] = $data['name'];
            }

            if (isset($data['description'])) {
                $taskData['description'] = $data['description'];
            }

            if (isset($data['startDateTime'])) {
                $taskData['startDateTime'] = new DateTime($data['startDateTime']);
            }

            if (isset($data['completionDateTime'])) {
                $taskData['completionDateTime'] = new DateTime($data['completionDateTime']);
            }

            if (isset($data['priority'])) {
                $taskData['priority'] = TaskPriority::from($data['priority']);
            }

            if (isset($data['status'])) {
                $taskData['status'] = WorkStatus::from($data['status']);
            } else {
                $taskData['status'] = WorkStatus::getStatusFromDates(
                    $taskData['startDateTime'] ?? $task->getStartDateTime(),
                    $taskData['completionDateTime'] ?? $task->getCompletionDateTime()
                );
            }

            if ($taskData && count($taskData) > 1) {
                $validator->validateDateBounds(
                    $taskData['startDateTime'] ?? $task->getStartDateTime(),
                    $taskData['completionDateTime'] ?? $task->getCompletionDateTime(),
                    $project->getStartDateTime(),
                    $project->getCompletionDateTime()
                );

                if ($validator->hasErrors()) {
                    throw new ValidationException('Task Validation Failed.', $validator->getErrors());
                }

                TaskModel::save($taskData);
            }

            Response::success(['projectId' => UUID::toString($project->getPublicId())], 'Project edited successfully.');
        } catch (ValidationException $e) {
            Response::error('Project Edit Failed.', $e->getErrors(), 422);
        } catch (NotFoundException $e) {
            Response::error('Project Edit Failed.', ['Project not found.'], 404);
        } catch (ForbiddenException $e) {
            Response::error('Project Edit Failed. ' . $e->getMessage(), [], 403);
        } catch (Exception $e) {
            Response::error('Project Edit Failed.', ['An unexpected error occurred. Please try again later.'], 500);
        }
    }
}