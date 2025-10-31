<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Container\TaskContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Enumeration\Role;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use InvalidArgumentException;

class TaskController implements Controller
{
    private function __construct()
    {
    }

    public static function index(array $args = []): void
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

            $tasks = Role::isProjectManager(Me::getInstance())
                ? $tasks = TaskModel::findAllByProjectId(Me::getInstance()->getId())
                : null;
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



    

    public static function viewTask(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null;
        if (!$projectId)
            throw new InvalidArgumentException('Project ID is required.');
        $taskId = $args['taskId'] ?? null;
        if (!$taskId)
            throw new InvalidArgumentException('Task ID is required.');

        $project = ProjectModel::all()->getItems()[0]; // Temporary placeholder
        $task = TaskModel::all()->getItems()[0]; // Temporary placeholder

        require_once SUB_VIEW_PATH . 'task.php';
    }

}