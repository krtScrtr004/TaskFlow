<?php

use LDAP\Result;

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

    public static function cancelProject(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Cannot decode data.');
        }

        $projectId = $data['projectId'] ?? null;
        if (!$projectId) {
            Response::error('Project ID is required.');
        }

        // TODO: Validate projectId

        Response::success([], 'Project cancelled successfully.');
    }
}
