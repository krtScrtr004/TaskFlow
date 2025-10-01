<?php

class ProjectController implements Controller
{
    private function __construct()
    {
    }

    public static function index(): void
    {
        // TODO: Dummy

        $projects = ProjectModel::all();
        $project = $projects[0];

        require_once VIEW_PATH . 'project.php';
    }
}
