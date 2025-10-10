<?php

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

        $project = ProjectModel::all()[0];

        $queryParams = $_GET ?? [];
        $filter = isset($queryParams['filter']) ? $queryParams['filter'] : '';

        // If key is not provided, all tasks of the project
        $tasks = $project->getTasks();

        require_once VIEW_PATH . 'task.php';
    }

    public static function viewTask(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null;
        if (!$projectId)
            throw new InvalidArgumentException('Project ID is required.');
        $taskId = $args['taskId'] ?? null;
        if (!$taskId)
            throw new InvalidArgumentException('Task ID is required.');

        $project = ProjectModel::all()[0]; // Temporary placeholder
        $task = TaskModel::all()->getItems()[0]; // Temporary placeholder

        require_once SUB_VIEW_PATH . 'view-task.php';
    }

    public static function getTaskById(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if ($projectId === null)
            throw new InvalidArgumentException('Project ID is required to get a task.');

        $taskId = $args['taskId'] ?? null; // Temporary placeholder
        if ($taskId === null)
            throw new InvalidArgumentException('Task ID is required to get a task.');

        $tasks = TaskModel::all()->getItems(); // Temporary placeholder
        Response::success($tasks, 'Task fetched successfully.');
    }

    public static function getTaskByKey(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if ($projectId === null)
            throw new InvalidArgumentException('Project ID is required to get tasks.');

        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        if ($offset < 0)
            throw new InvalidArgumentException('Invalid offset value.');

        if ($offset > 20)
            Response::success([], 'No more tasks to load.');

        $tasks = TaskModel::all()->getItems(); // Temporary placeholder
        Response::success($tasks, 'Task fetched successfully.');
    }

    public static function addTask(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if ($projectId === null)
            throw new InvalidArgumentException('Project ID is required to add a task.');

        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // TODO: Validate and sanitize $data
        // TODO: Add task to the database
        // Required fields:
        // name,
        // startDateTime,
        // completionDateTime,
        // description,
        // priority,
        // assignedWorkers - array of worker IDs
        // projectId

        Response::success([
            'id' => uniqid()
        ], 'Task added successfully.');
    }

    public static function editTask(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if ($projectId === null)
            throw new InvalidArgumentException('Project ID is required to edit a task.');
        $taskId = $args['taskId'] ?? null; // Temporary placeholder
        if ($taskId === null)
            throw new InvalidArgumentException('Task ID is required to edit a task.');

        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        Response::success([], 'Task updated successfully.');
    }
}