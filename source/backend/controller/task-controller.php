<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Container\TaskContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Enumeration\Role;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use DateTime;
use Error;
use InvalidArgumentException;
use ValueError;

class TaskController implements Controller
{
    private function __construct()
    {
    }

    public static function index(): void
    {
    }

    /**
     * Displays the grid view of tasks for a specific project.
     *
     * This method performs the following actions:
     * - Checks if the user has an authorized session.
     * - Validates and parses the project ID from the arguments.
     * - Retrieves the project by its ID and ensures it exists.
     * - Optionally filters tasks by a search key or a single filter (WorkStatus or TaskPriority) from query parameters.
     * - Supports pagination via 'offset' and 'limit' query parameters.
     * - Fetches tasks for the project using the provided filters and options.
     * - Loads the grid view for tasks.
     * - Handles forbidden and not found exceptions by delegating to the error controller.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier (required)
     * 
     * Query parameters (via $_GET):
     *      - key: string (optional) Search keyword for tasks
     *      - filter: string (optional) Filter by WorkStatus or TaskPriority; 'all' disables filtering
     *      - offset: int (optional) Pagination offset (default: 0)
     *      - limit: int (optional) Pagination limit (default: 50)
     * 
     * @return void
     * 
     * @throws ForbiddenException If the session is unauthorized or projectId is missing
     * @throws NotFoundException If the project does not exist
     */
    public static function viewGrid(array $args): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
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

            // NOTE: No need for active phase check when viewing tasks in grid view

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
                'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            ];

            // Get all tasks from the project
            $tasks = TaskModel::search(
                $key,
                Me::getInstance()->getId(),
                null,
                $projectId,
                $filter,
                $options
            );
            if (!$tasks) {
                // No tasks found, assign an empty container
                $tasks = new TaskContainer();
            }
            require_once VIEW_PATH . 'tasks.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }


    /**
     * Displays detailed information about a specific task within a project phase.
     *
     * This method performs the following actions:
     * - Checks if the current session is authorized.
     * - Validates and converts projectId, phaseId, and taskId from the input arguments to UUID objects.
     * - Retrieves the corresponding Project, Phase, and Task models from the database.
     * - Throws appropriate exceptions if any entity is not found or required IDs are missing.
     * - Checks the task's status and start date; if the task is pending and its start date has passed, updates its status to ongoing.
     * - Loads the task sub-view for rendering.
     * - Handles forbidden and not found errors by delegating to the ErrorController.
     *
     * @param array $args Associative array containing identifiers:
     *      - projectId: string|UUID Project identifier
     *      - phaseId: string|UUID Phase identifier
     *      - taskId: string|UUID Task identifier
     * 
     * @return void
     * 
     * @throws ForbiddenException If session is unauthorized or required IDs are missing
     * @throws NotFoundException If project, phase, or task is not found
     */
    public static function viewInfo(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if ($project === null) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = PhaseModel::findById($phaseId);
            if ($phase === null) {
                throw new NotFoundException('Phase not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $phase->getId(), $project->getId());
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            $status = $task->getStatus();
            $startDateTime = $task->getStartDateTime();
            $completionDateTime = $task->getCompletionDateTime();
            $currentDateTime = new DateTime();  

            // Check if the task is already ongoing
            if ($startDateTime && $currentDateTime >= $startDateTime && $status === WorkStatus::PENDING) {
                $task->setStatus(WorkStatus::ON_GOING);
                TaskModel::save([
                    'id' => $task->getId(),
                    'status' => WorkStatus::ON_GOING
                ]);
            } 
            require_once SUB_VIEW_PATH . 'task.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

}