<?php

class ProjectController implements Controller
{
    private function __construct() {}

    public static function index(): void
    {
        // TODO: 

        $start = new DateTime('2023-01-01 12:00:00');
        $end = new DateTime('2023-12-31 23:59:59');
        $completed = new DateTime('2023-11-30 18:30:00');
        $status = ProjectTaskStatus::getStatusFromDates($start, $end);

        $project = new Project(
            uniqid(),
            'New Project',
            'This is a new project created for testing purposes.',
            Me::getInstance(),
            10000000,
            null,
            $start,
            $end,
            $completed,
            $status,
            new DateTime()
        );

        require_once VIEW_PATH . 'project.php';
    }
}
