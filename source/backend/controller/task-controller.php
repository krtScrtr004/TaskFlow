<?php

namespace App\Controller;

use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use InvalidArgumentException;

class TaskController implements Controller
{
    public static function index(array $args = []): void
    {
        // TODO

        $projectId = $args['projectId'] ?? null; // Temporary placeholder   
        if (!$projectId)
            throw new InvalidArgumentException('Project ID is required.');

        $priority = $_GET['priority'] ?? null; // Temporary placeholder
        $status = $_GET['status'] ?? null; // Temporary placeholder

        // TODO: 
        // Fetch tasks for the given project ID if PM;
        // else, fetch tasks assigned to the worker

        $project = ProjectModel::all()->getItems()[0]; // Temporary placeholder

        $queryParams = $_GET ?? [];
        $filter = isset($queryParams['filter']) ? $queryParams['filter'] : '';

        // If key is not provided, all tasks of the project
        $tasks = $project->getTasks();

        require_once VIEW_PATH . 'tasks.php';
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