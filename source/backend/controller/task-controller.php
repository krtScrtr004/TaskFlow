<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Container\TaskContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Model\TaskModel;
use App\Service\TaskService;
use DateTime;
use ValueError;

class TaskController implements Controller
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

    public static function index(): void {}

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

            $instance = new self();

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

            $phase = $phaseId
                ? $instance->phaseModel->findById($phaseId)
                : null;
            if (isset($args['phaseId']) && !$phase) {
                throw new NotFoundException('Phase not found.');
            }

            $workerId = isset($args['workerId'])
                ? UUID::fromString($args['workerId'])
                : null;
            if (isset($args['workerId']) && !$workerId) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = $workerId
                ? $instance->projectWorkerModel->findById($workerId)
                : null;
            if (isset($args['workerId']) && !$worker) {
                throw new NotFoundException('Worker not found.');
            }

            // NOTE: No need for active phase check when viewing tasks in grid view

            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $key = trim($_GET['key']);
            }

            // Obtain filter from query parameters
            $status = isset($_GET['status'])
                ? WorkStatus::from($_GET['status'])
                : null;
            $priority = isset($_GET['priority'])
                ? TaskPriority::from($_GET['priority'])
                : null;

            $options = [
                'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            ];

            // Get all tasks from the project
            $tasks = $instance->taskModel->search(
                $key,
                $worker?->getId() ?? Me::getInstance()->getId(),
                $phase?->getId() ?? null,
                $projectId,
                $status,
                $priority,
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

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = $instance->projectModel->findById($projectId);
            if ($project === null) {
                throw new NotFoundException('Project not found.');
            }

            $phaseId = isset($args['phaseId'])
                ? UUID::fromString($args['phaseId'])
                : null;
            if (!$phaseId) {
                throw new ForbiddenException('Phase ID is required.');
            }

            $phase = $instance->phaseModel->findById($phaseId);
            if ($phase === null) {
                throw new NotFoundException('Phase not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskService::get($taskId, ['workers' => true, 'resources' => true]);
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            $status = $task->getStatus();
            $startDateTime = formatDateTime($task->getStartDateTime(), 'Y-m-d');
            $completionDateTime = formatDateTime($task->getCompletionDateTime(), 'Y-m-d');
            $currentDateTime = formatDateTime(new DateTime(), 'Y-m-d');

            // Check if the task is already ongoing
            if ($startDateTime && compareDates($startDateTime, $currentDateTime) <= 0 && $status === WorkStatus::PENDING) {
                $task->setStatus(WorkStatus::ONGOING);
                $instance->taskModel->save([
                    'id' => $task->getId(),
                    'status' => WorkStatus::ONGOING
                ]);
            } elseif (
                $completionDateTime && compareDates($completionDateTime, $currentDateTime) < 0 &&
                ($status === WorkStatus::PENDING || $status === WorkStatus::ONGOING)
            ) {
                $instance->taskModel->save([
                    'id' => $task->getId(),
                    'status' => WorkStatus::DELAYED
                ]);
            }
            require_once SUB_VIEW_PATH . 'task.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    public static function viewTaskForm(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            // Task Info
            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (isset($args['taskId']) && !$taskId)
                throw new ForbiddenException('Task ID is required.');

            $task = isset($args['taskId'])
                ? TaskService::get($taskId, ['workers' => true, 'resources' => true])
                : null;
            if (isset($args['taskId']) && !$task)
                throw new NotFoundException('Task not found.');

            // Project Info
            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId)
                throw new ForbiddenException('Project ID is required.');

            $instance = new self();

            /**
             * If projectId is provided in args, use it to fetch the project (Create form).
             * Otherwise, retrieve the owning project of the task (Edit form).
             */
            $project = isset($projectId)
                ? $instance->projectModel->findById($projectId)
                : $instance->taskModel->findOwningProject($task->getId());
            if (isset($args['projectId']) && !$project)
                throw new NotFoundException('Project not found.');

            // Phase Info (Active)
            $phase = isset($args['projectId'])
                ? $instance->phaseModel->findOnGoingByProjectId($projectId)
                : $instance->taskModel->findOwningPhase($task->getId());
            if (!$phase) throw new NotFoundException('Active phase not found.');

            require_once SUB_VIEW_PATH . 'form' . DS . 'task.php';
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }
}
