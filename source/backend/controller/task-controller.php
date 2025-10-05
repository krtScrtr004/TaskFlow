<?php

class TaskController implements Controller {
    public static function index(array $args = []): void {
        // TODO

        if (!isset($args['projectId'])) {
            throw new InvalidArgumentException('Project ID is required.');
        }

        // TODO: Fetch tasks for the given project ID

        $project = ProjectModel::all()[0];
        $tasks = $project->getTasks();

        require_once VIEW_PATH . 'task.php';
    }
}