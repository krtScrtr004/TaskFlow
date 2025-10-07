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

    public static function createProject(): void
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // TODO: Validate and sanitize $data

        Response::success([], 'Project created successfully.', 201);
    }

    public static function editProject(array $args = []): void
    {
        /** 
         * Requirements :
         * Project
         * - Project : ID, Description, Budget, Start Date, Completion Date
         * 
         * Phases
         * - Edited Phases : ID, Description, Start Date, Completion Date
         * - New Phases :Name, Description, Start Date, Completion Date
         * - Cancelled Phases : ID
         * */ 

        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if (!$projectId)
            throw new InvalidArgumentException('Project ID is required.');

        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // TODO: Validate and sanitize $data

        $phasesToAdd = $data['phasesToAdd'] ?? [];
        // TODO: Use PhaseController to add phases and get their IDs

        Response::success([], 'Project edited successfully.');
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
