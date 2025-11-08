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
use App\Model\ProjectModel;
use App\Model\TaskModel;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use ValueError;

class TaskEndpoint
{
    /**
     * Retrieves a task by its ID within a specific project context.
     *
     * This method performs the following actions:
     * - Validates that the request method is GET.
     * - Ensures the user session is authorized.
     * - Validates and converts the provided projectId and taskId to UUID objects.
     * - Checks if the specified project exists (if projectId is provided).
     * - Fetches the task by its ID and associated project ID.
     * - Returns a success response with the task data if found.
     * - Handles and responds to various exceptions (validation, forbidden, not found, unexpected errors).
     *
     * @param array $args Associative array of arguments with the following keys:
     *      - projectId: string|UUID|null (optional) The ID of the project containing the task.
     *      - taskId: string|UUID The ID of the task to retrieve.
     *
     * @return void Outputs a JSON response with the task data or an error message.
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
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            if (isset($args['projectId']) && ProjectModel::findById($projectId) === null) {
                throw new NotFoundException('Project not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $projectId);
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            Response::success([$task], 'Task fetched successfully.');
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
     * - Requires a valid session and GET request method.
     *
     * @param array $args Optional arguments for task retrieval:
     *      - projectId: string|UUID|null Project identifier to filter tasks (optional)
     *
     * Query Parameters (via $_GET):
     *      - key: string (optional) Search keyword for tasks
     *      - status: string (optional) Status to filter tasks (must be a valid WorkStatus)
     *      - limit: int (optional) Maximum number of tasks to return (default: 10)
     *      - offset: int (optional) Number of tasks to skip for pagination (default: 0)
     *
     * @throws ForbiddenException If the request method is not GET, session is unauthorized, or projectId is invalid
     * @throws ValidationException If validation of parameters fails
     * @throws Exception For any other unexpected errors
     *
     * Responds with:
     *      - 200: Success, with an array of tasks or an empty array if none found
     *      - 403: Forbidden, if authentication or authorization fails
     *      - 422: Validation failed, with error details
     *      - 500: Unexpected server error
     */
    public static function getTaskByKey(array $args = []): void
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
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            // Check if 'key' parameter is present in the query string
            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $key = trimOrNull($_GET['key'] ?? '') ?? '';
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

            $tasks = null;
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $key = trimOrNull($_GET['key']);
                $tasks = TaskModel::search(
                    $key, 
                    Me::getInstance()->getId(), 
                    $projectId, 
                    $filter, 
                    $options
                );
            } else {
                $tasks = Role::isProjectManager(Me::getInstance())
                    ? TaskModel::findAllByProjectId($projectId, $filter, $options)
                    : TaskModel::findAssignedToWorker(Me::getInstance()->getId(), $projectId, $filter, $options);
            }
    
            if (!$tasks) {
                Response::success([], 'No tasks found for the specified project.');
            } else {
                $return = [];
                foreach ($tasks as $worker) {
                    $return[] = $worker;
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
     * Adds a new task to a project with associated workers.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized.
     * - Validates CSRF token.
     * - Decodes input data from the request body.
     * - Validates the presence and existence of the project ID.
     * - Validates task date bounds against the project's date range.
     * - Creates a partial Task instance with provided data.
     * - Validates and associates worker IDs with the task.
     * - Adds the project ID as additional info to the task.
     * - Persists the new task using the TaskModel.
     * - Returns a success response or handles validation/authorization errors.
     *
     * @param array $args Associative array of arguments with the following keys:
     *      - projectId: string|UUID The public identifier of the project to which the task will be added.
     *
     * Input data (decoded from request body) must include:
     *      - name: string Task name
     *      - description: string|null Task description
     *      - startDateTime: string Task start date/time (ISO 8601 format)
     *      - completionDateTime: string Task completion date/time (ISO 8601 format)
     *      - priority: mixed Task priority
     *      - workerIds: array List of worker public IDs (string or UUID)
     *
     * @throws ForbiddenException If the user is not authorized, project ID is missing, or worker IDs are invalid.
     * @throws ValidationException If input data is invalid or task validation fails.
     * @throws NotFoundException If the specified project does not exist.
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
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $validator = new WorkValidator();
            $validator->validateDateBounds(
                new DateTime($data['startDateTime']),
                new DateTime($data['completionDateTime']),
                $project->getStartDateTime(),
                $project->getCompletionDateTime()
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
            $task->addAdditionalInfo('projectId', $project->getId());

            TaskModel::create($task);
            
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
     * Edits an existing task within a project.
     *
     * This method performs the following operations:
     * - Checks if the user session is authorized to edit projects.
     * - Validates CSRF token for request protection.
     * - Validates and parses the provided projectId and taskId.
     * - Retrieves the task and its associated project.
     * - Decodes and validates input data for task editing.
     * - Updates task fields such as description, start/completion dates, priority, and status.
     * - Validates that the task's date bounds are within the project's date bounds.
     * - Saves the updated task data if validation passes.
     * - Returns a success response with the project public ID, or an error response on failure.
     *
     * @param array $args Associative array containing the following keys:
     *      - projectId: string|UUID|null (optional) The public identifier of the project.
     *      - taskId: string|UUID The public identifier of the task to edit.
     *
     * Input data (decoded from request body) may include:
     *      - description: string (optional) The updated task description.
     *      - startDateTime: string (optional) The new start date/time (ISO 8601 format).
     *      - completionDateTime: string (optional) The new completion date/time (ISO 8601 format).
     *      - priority: string|TaskPriority (optional) The updated task priority.
     *      - status: string|WorkStatus (optional) The updated task status.
     *
     * @throws ForbiddenException If the user session is not authorized.
     * @throws ValidationException If input data is invalid or fails validation.
     * @throws NotFoundException If the task or project is not found.
     * @throws Exception For any other unexpected errors.
     *
     * @return void
     */
    public static function edit(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not allowed to edit projects.');
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($projectId) && !$projectId) {
                throw new ValidationException('Project ID is required to edit a task.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ValidationException('Task ID is required to edit a task.');
            }

            $task = TaskModel::findById($taskId, $projectId);
            if (!$task) {
                throw new NotFoundException('Task is not found.');
            }

            $project = null;
            if (isset($projectId)) {
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