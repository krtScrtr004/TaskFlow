<?php

namespace App\Endpoint;

use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use InvalidArgumentException;

class TaskEndpoint
{
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

    public static function add(array $args = []): void
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

    public static function edit(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if ($projectId === null)
            throw new InvalidArgumentException('Project ID is required to edit a task.');
        $taskId = $args['taskId'] ?? null; // Temporary placeholder
        if ($taskId === null)
            throw new InvalidArgumentException('Task ID is required to edit a task.');

        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.', []);

        Response::success([], 'Task updated successfully.');
    }
}