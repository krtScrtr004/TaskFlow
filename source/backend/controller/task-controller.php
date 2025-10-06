<?php

class TaskController implements Controller
{
    public static function index(array $args = []): void
    {
        // TODO

        $projectId = $args['projectId'] ?? null; // Temporary placeholder   
        if (!$projectId) 
            throw new InvalidArgumentException('Project ID is required.');

        // TODO: 
        // Fetch tasks for the given project ID if PM;
        // else, fetch tasks assigned to the worker

        $project = ProjectModel::all()[0];
        $tasks = $project->getTasks();

        require_once VIEW_PATH . 'task.php';
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
            'redirectUrl' => REDIRECT_PATH . "project/$projectId/task"
        ], 'Task added successfully.');
    }
}