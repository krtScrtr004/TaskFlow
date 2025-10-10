<?php

use LDAP\Result;

class ProjectController implements Controller
{
    private function __construct()
    {
    }

    public static function index(): void {}

    public static function viewProject(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if (!$projectId)
            throw new InvalidArgumentException('Project ID is required.');

        $projects = ProjectModel::all();
        $project = $projects[0];

        require_once VIEW_PATH . 'project.php';
    }

    public static function createProject(): void
    {
        /**
         * Requirements:
         * Project:
         * - Name: string
         * - Description: string
         * - Budget: float
         * - Start Date: string (YYYY-MM-DD)
         * - Completion Date: string (YYYY-MM-DD)
         * 
         * Phases: Array
         * - Name: string
         * - Description: string
         * - Start Date: string (YYYY-MM-DD)
         * - Completion Date: string (YYYY-MM-DD)
         */
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // TODO: Validate and sanitize $data

        Response::success(['id' => uniqid()], 'Project created successfully.', 201);
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

        Response::success(['id' => $projectId], 'Project edited successfully.');
    }
}
