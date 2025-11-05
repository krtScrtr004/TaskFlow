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
use App\Model\ProjectModel;
use App\Model\TaskModel;
use Error;
use InvalidArgumentException;
use ValueError;

class TaskController implements Controller
{
    private function __construct()
    {
    }

    public static function index(): void {}

    /**
     * Displays the task grid view for a specific project.
     *
     * This method checks user session authorization and validates the provided project ID.
     * It retrieves the list of tasks for the project, either all tasks (if the user is a project manager)
     * or only those assigned to the current worker. If no tasks are found, an empty TaskContainer is used.
     * The method then loads the corresponding view for displaying tasks.
     * Handles forbidden and not found exceptions by delegating to the error controller.
     *
     * @param array $args Associative array of arguments with the following keys:
     *      - projectId: string|UUID The public identifier of the project to view tasks for.
     *
     * @throws ForbiddenException If the user is not authorized or projectId is missing.
     * @throws NotFoundException If the specified project does not exist.
     *
     * @return void
     */
    public static function viewGrid(array $args): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            $projectId = isset($args['projectId']) 
                ? UUID::fromString($args['projectId']) 
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            } elseif (ProjectModel::findById($projectId) === null) {
                throw new NotFoundException('Project not found.');
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

    public static function viewInfo(array $args = []): void
    {
        try {
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
            if ($project === null) {
                throw new NotFoundException('Project not found.');
            }

            $taskId = isset($args['taskId']) 
                ? UUID::fromString($args['taskId']) 
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $project->getId());
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            require_once SUB_VIEW_PATH . 'task.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

}