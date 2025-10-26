<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\PhaseContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Dependent\Phase;
use App\Entity\Project;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Validator\UuidValidator;
use App\Middleware\Csrf;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use InvalidArgumentException;

class ProjectEndpoint
{
    public static function create(): void
    {
        try {
            if (SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User sessions are not allowed to create projects.');
            }
            Csrf::protect();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $project = $data['project'] ?? null;
            if (!$project || empty($project)) {
                throw new ValidationException('Project data is required.');
            }

            $phases = $data['phases'] ?? null;
            if (!$phases || empty($phases)) {
                throw new ValidationException('Phases data is required.');
            }

            // Check if user has active project 
            if (ProjectModel::findByManagerId(Me::getInstance()->getId())) {
                throw new ForbiddenException('User already has an active project.');
            }

            self::sanitizeData($project);

            $phasesContainer = new PhaseContainer();
            foreach ($phases as &$phase) {
                self::sanitizeData($phase);

                // Determine phase status
                $phase['status'] = WorkStatus::getStatusFromDates(new DateTime($phase['startDateTime']), new DateTime($phase['completionDateTime']));

                // Create partial Phase entity and add to container
                $phasesContainer->add(Phase::createPartial($phase));
            }

            // Create partial Project entity
            $newProject = Project::createPartial([
                'name' => $project['name'],
                'description' => $project['description'],
                'budget' => floatval($project['budget']) ?? 0.00,
                'startDateTime' => $project['startDateTime'],
                'completionDateTime' => $project['completionDateTime'],
                'phases' => $phasesContainer,
                'tasks' => [],
                'status' => WorkStatus::getStatusFromDates(new DateTime($project['startDateTime']), new DateTime($project['completionDateTime']))
            ]);
            $newProject = ProjectModel::create($newProject);

            Response::success([
                'projectId' => UUID::toString($newProject->getPublicId())
            ], 'Project created successfully.', 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getErrors(), 422);
        } catch (ForbiddenException $e) {
            Response::error('Project Creation Failed. ' . $e->getMessage(), [], 403);
        } catch (Exception $e) {
            Response::error('Project Creation Failed.', ['An unexpected error occurred. Please try again later.'], 500);
        }
    }

    private static function sanitizeData(
        array &$data,
        array $trimmableFields = [
            'name',
            'description',
            'startDateTime',
            'completionDateTime',
            'actionDateTime'
        ]
    ): void {
        foreach ($data as $key => $value) {
            if (in_array($key, $trimmableFields, true)) {
                $data[$key] = trim($value);
            }
        }
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