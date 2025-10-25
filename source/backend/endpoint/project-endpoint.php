<?php

namespace App\Endpoint;

use App\Exception\ValidationException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Validator\UuidValidator;
use InvalidArgumentException;

class ProjectEndpoint
{
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
        if (!$data) {
            Response::error('Cannot decode data.');
        }

        // TODO: Validate and sanitize $data

        Response::success(['id' => uniqid()], 'Project created successfully.', 201);
    }



















    public static function getProjectById(array $args = []): void
    {
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if (!$projectId)
            Response::error('Project ID is required.');

        $projects = ProjectModel::all();
        Response::success([$projects->getItems()[0]], 'Project retrieved successfully.');
    }

    public static function getProjectByKey(): void
    {
        $key = $_GET['key'] ?? '';

        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        $projects = ProjectModel::all();
        Response::success([$projects->getItems()[0]], 'Projects retrieved successfully.');
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
            Response::error('Cannot decode data.', []);

        // TODO: Validate and sanitize $data

        $phasesToAdd = $data['phasesToAdd'] ?? [];
        // TODO: Use PhaseController to add phases and get their IDs

        Response::success(['id' => $projectId], 'Project edited successfully.');
    }
}