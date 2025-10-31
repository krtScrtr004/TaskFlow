<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\PhaseContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Dependent\Phase;
use App\Entity\Project;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Validator\UuidValidator;
use App\Middleware\Csrf;
use App\Model\TaskModel;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use InvalidArgumentException;

class ProjectEndpoint
{
        /**
     * Creates a new project with associated phases.
     *
     * This endpoint handles project creation with the following validations and operations:
     * - Verifies that the request is from an API client (not a user session)
     * - Validates CSRF token
     * - Validates required project and phases data
     * - Ensures user doesn't already have an active project
     * - Sanitizes all input data
     * - Determines phase and project status based on dates
     * - Creates partial Phase entities and adds them to a container
     * - Creates and persists the project with all phases
     *
     * @throws ForbiddenException If user session attempts to create project or user already has active project (403)
     * @throws ValidationException If data cannot be decoded or required fields are missing/empty (422)
     * @throws Exception If an unexpected error occurs during project creation (500)
     *
     * @return void Sends JSON response with projectId on success (201) or error message on failure
     * 
     * Expected input format (php://input):
     * {
     *     "project": {
     *         "name": string,
     *         "description": string,
     *         "budget": float,
     *         "startDateTime": string (datetime),
     *         "completionDateTime": string (datetime)
     *     },
     *     "phases": [
     *         {
     *             "startDateTime": string (datetime),
     *             "completionDateTime": string (datetime),
     *             ...other phase fields
     *         }
     *     ]
     * }
     * 
     * Success response (201):
     * {
     *     "projectId": string (UUID)
     * }
     * 
     * Error responses:
     * - 422: Validation errors
     * - 403: Forbidden (session user or duplicate project)
     * - 500: Unexpected server error
     */
    public static function create(): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not allowed to create projects.');
            }

            if (!Role::isProjectManager(Me::getInstance())) {
                throw new ForbiddenException('Only Project Managers can create projects.');
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

            $index = 0;
            $phasesContainer = new PhaseContainer();
            foreach ($phases as &$phase) {
                self::sanitizeData($phase);

                // Temporarily assign index as ID
                $phase['id'] = $index++;
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

    /**
     * Edits an existing project and its associated phases.
     *
     * This endpoint handles project editing including:
     * - Updates project details (description, budget, dates, status)
     * - Edits existing phases (description, dates, status)
     * - Adds new phases to the project
     * - Cancels phases by updating their status
     *
     * Expected data structure in request body:
     * {
     *   "project": {
     *     "description": string,
     *     "budget": float,
     *     "startDateTime": string (ISO 8601 format),
     *     "completionDateTime": string (ISO 8601 format)
     *   },
     *   "phase": {
     *     "toEdit": [
     *       {
     *         "id": string (UUID),
     *         "description": string,
     *         "startDateTime": string (ISO 8601 format),
     *         "completionDateTime": string (ISO 8601 format)
     *       }
     *     ],
     *     "toAdd": [
     *       {
     *         "name": string,
     *         "description": string,
     *         "startDateTime": string (ISO 8601 format),
     *         "completionDateTime": string (ISO 8601 format)
     *       }
     *     ],
     *     "toCancel": [
     *       {
     *         "id": string (UUID)
     *       }
     *     ]
     *   }
     * }
     *
     * @param array $args Associative array containing route parameters:
     *      - projectId: string UUID of the project to edit
     * 
     * @return void Outputs JSON response directly
     * 
     * @throws ValidationException When project ID is missing, data is invalid, or phases data is malformed (HTTP 422)
     * @throws ForbiddenException When user sessions attempt to edit projects (HTTP 403)
     * @throws NotFoundException When project with given ID is not found (HTTP 404)
     * @throws Exception For any unexpected errors (HTTP 500)
     * 
     * @response success JSON with projectId on successful edit
     * @response error JSON with error message and details on failure
     */
    public static function edit(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not allowed to edit projects.');
            }

            if (!Role::isProjectManager(Me::getInstance())) {
                throw new ForbiddenException('Only Project Managers can edit projects.');
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ValidationException('Project ID is required to edit a project.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project is not found.');
            }

            $validator = new WorkValidator();

            $projectData = ['id' => $project->getId()];

            if (isset($data['project']['description'])) {
                $projectData['description'] = $data['project']['description'];
            }

            if (isset($data['project']['budget'])) {
                $projectData['budget'] = floatval($data['project']['budget']) ?? 0.00;
            }

            if (isset($data['project']['startDateTime'])) {
                $projectData['startDateTime'] = new DateTime($data['project']['startDateTime']);
            }

            if (isset($data['project']['completionDateTime'])) {
                $projectData['completionDateTime'] = new DateTime($data['project']['completionDateTime']);
            }

            if (isset($data['project']['status'])) {
                $projectData['status'] = WorkStatus::from($data['project']['status']);
            } else {
                $projectData['status'] = WorkStatus::getStatusFromDates(
                    $projectData['startDateTime'] ?? $project->getStartDateTime(),
                    $projectData['completionDateTime'] ?? $project->getCompletionDateTime()
                );
            }

            $phases = [
                'toEdit' => [],
                'toAdd' => new PhaseContainer(),
            ];

            $phasesArray = $data['phase'] ?? [];
            foreach ($phasesArray as $key => &$arr) {
                foreach ($arr as &$value) {
                    self::sanitizeData($value);

                    if ($key === 'toEdit') {
                        // Phase to edit
                        $validator->validateMultiple([
                            'description'           => $value['description'],
                            'startDateTime'         => new DateTime($value['startDateTime']),
                            'completionDateTime'    => new DateTime($value['completionDateTime'])
                        ]);
                        if ($validator->hasErrors()) {
                            throw new ValidationException('Invalid phase data for editing.', $validator->getErrors());
                        }
                        $phases['toEdit'][] = [
                            'publicId'              => UUID::fromString($value['id']),
                            'description'           => $value['description'],
                            'startDateTime'         => new DateTime($value['startDateTime']),
                            'completionDateTime'    => new DateTime($value['completionDateTime']),
                            'status'                => WorkStatus::getStatusFromDates(new DateTime($value['startDateTime']), new DateTime($value['completionDateTime']))
                        ];
                    } elseif ($key === 'toAdd') {
                        // New phase to add
                        $phases['toAdd']->add(Phase::createPartial([
                            'name'                  => $value['name'],
                            'description'           => $value['description'],
                            'startDateTime'         => new DateTime($value['startDateTime']),
                            'completionDateTime'    => new DateTime($value['completionDateTime']),
                            'status'                => WorkStatus::getStatusFromDates(new DateTime($value['startDateTime']), new DateTime($value['completionDateTime']))
                        ]));
                    } else {
                        // Phase to cancel
                        $phases['toEdit'][] = [
                            'publicId'              => UUID::fromString($value['id']),
                            'status'                => WorkStatus::CANCELLED
                        ];
                    }
                }
            }

            // Save project edits
            if ($projectData) {
                $validator->validateMultiple($projectData);
                if ($validator->hasErrors()) {
                    throw new ValidationException('Invalid project data for editing.', $validator->getErrors());
                }
                self::sanitizeData($projectData);
                ProjectModel::save($projectData);
            }
            if ($phases['toAdd']->count() > 0) {
                PhaseModel::createMultiple($project->getId(), $phases['toAdd']);
            }
            if (count($phases['toEdit']) > 0) {
                PhaseModel::saveMultiple($phases['toEdit']);
            }

            Response::success(['projectId' => UUID::toString($project->getPublicId())], 'Project edited successfully.');
        } catch (ValidationException $e) {
            Response::error('Project Edit Failed.', $e->getErrors(), 422);
        } catch (NotFoundException $e) {
            Response::error('Project Edit Failed.', ['Project not found.'], 404);
        } catch (ForbiddenException $e) {
            Response::error('Project Edit Failed. ' . $e->getMessage(), [], 403);
        } catch (Exception $e) {
            Response::error('Project Edit Failed.', ['An unexpected error occurred. Please try again later.'], 500);
        }
    }

    /**
     * Sanitizes data by trimming whitespace from specified fields.
     *
     * This method iterates through the provided data array and trims whitespace
     * from the beginning and end of values for fields specified in the trimmable
     * fields list. The data array is modified in place (passed by reference).
     *
     * @param array $data Associative array containing data to be sanitized.
     *                    The array is passed by reference and modified directly.
     * @param array $trimmableFields List of field names whose values should be trimmed.
     *                               Default fields include:
     *                               - name: Project name
     *                               - description: Project description
     *                               - startDateTime: Project start date/time
     *                               - completionDateTime: Project completion date/time
     *                               - actionDateTime: Project action date/time
     * 
     * @return void This method does not return a value; it modifies $data in place.
     */
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




}