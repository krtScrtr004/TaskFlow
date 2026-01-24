<?php

namespace App\Endpoint;

use App\Abstract\Endpoint;
use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\PhaseContainer;
use App\Container\WorkerContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Dependent\Phase;
use App\Dependent\Worker;
use App\Entity\Project;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Response;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Middleware\Csrf;
use App\Service\ProjectService;
use App\Utility\ResponseExceptionHandler;
use App\Validator\userValidator;
use App\Validator\UuidValidator;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use Throwable;
use ValueError;

class ProjectEndpoint extends Endpoint
{

    /**
     * Retrieves projects by key with optional filtering and pagination.
     *
     * This endpoint handles GET requests to fetch projects based on a search key, user authorization, and optional status filtering.
     * It validates the request method and session, parses query parameters, and returns a list of projects matching the criteria.
     *
     * Query parameters supported:
     * - key: string (optional) Search key for project lookup
     * - filter: string (optional) Status filter (e.g., WorkStatus value; 'all' for no filter)
     * - offset: int (optional) Pagination offset (default: 0)
     * - limit: int (optional) Pagination limit (default: 50)
     *
     * @param array $args Optional arguments for project lookup:
     *      - projectId: string|UUID|null Project identifier (optional)
     *
     * @throws ForbiddenException If the request method is not GET or session is unauthorized, or if projectId is invalid.
     * @throws ValidationException If validation fails for input parameters.
     * @throws Exception For unexpected errors.
     *
     * @return void Outputs a JSON response with:
     *      - Success: Array of projects and a success message
     *      - Failure: Error message and appropriate HTTP status code
     */
    public static function getByKey(array $args = []): void
    {
        try {
            self::rateLimit();

            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            // Check if 'key' parameter is present in the query string
            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $key = trim($_GET['key']);
            }

            // Obtain filter from query parameters (one filter type only)
            $status = null;
            if (isset($_GET['filter']) && strcasecmp($_GET['filter'], 'all') !== 0) {
                $filterValue = $_GET['filter'];
                // Try to parse as WorkStatus first, then TaskPriority if later fails
                try {
                    $status = WorkStatus::from($filterValue);
                } catch (ValueError $e) {
                    // Do nothing
                }
            }

            $options = [
                'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 50,
            ];

            $projects = ProjectModel::search(
                $key,
                Me::getInstance()->getId(),
                $status,
                $options
            );

            if (!$projects) {
                Response::success([], 'No tasks found for the specified project.');
            } else {
                $return = [];
                foreach ($projects as $project) {
                    $return[] = $project;
                }
                Response::success($return, 'Tasks fetched successfully.');
            }
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Fetch Projects Failed.', $e);
        }
    }

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
     * - Creates partial Worker entities and adds them to a container
     * - Creates and persists the project with all phases
     * 
     * @param array $args Associative array containing route parameters (not used here)
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
     *     ],
     *    "workers": [
     *        {
     *             "id": string (UUID),
     *             "defaultRate": float
     *         }
     *    ]
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
    public static function create(array $args = []): void
    {
        try {
            self::formRateLimit();

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            if (!Role::isProjectManager(Me::getInstance()->getRole())) {
                throw new ForbiddenException('Only Project Managers can create projects.');
            }

            // Check if user has active project 
            if (ProjectModel::findManagerActiveProjectByManagerId(Me::getInstance()->getId())) {
                throw new ForbiddenException('User already has an active project.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.', ['Invalid JSON data.']);
            }

            $project = $data['project'] ?? null;
            if (!$project || empty($project)) {
                throw new ValidationException('Project data is required.', ['Project details are required.']);
            }

            $phases = $data['phases'] ?? null;
            if (!$phases || empty($phases)) {
                throw new ValidationException('Phases data is required.', ['At least one phase is required.']);
            }

            $workers = $data['workers'] ?? null;
            if (!$workers || empty($workers)) {
                throw new ValidationException('Workers data is required.', ['At least one worker is required.']);
            }

            $maximumWorkers = (int) $project['maxWorkers'];
            if (count($workers) > $maximumWorkers) {
                throw new ValidationException('Exceeded maximum number of workers.', [
                    "Number of workers exceeds the maximum allowed ({$maximumWorkers})."
                ]);
            }

            sanitizeData($project);

            $workValidator = new WorkValidator();
            $userValidator = new userValidator();

            $projectPhaseBudgetValidator = $workValidator->createBudgetBoundaryValidator($project['budget'] ?? 0.00);

            $totalPhasesBudget = 0.00;
            
            $index = 0;
            $phasesContainer = new PhaseContainer();
            foreach ($phases as &$phase) {
                $workValidator->validateDateBounds(
                    new DateTime($phase['startDateTime']),
                    new DateTime($phase['completionDateTime']),
                    new DateTime($project['startDateTime']),
                    new DateTime($project['completionDateTime'])
                );
                $projectPhaseBudgetValidator['addBudget']((float) $phase['budget'] ?? 0.00);
                if ($workValidator->hasErrors()) {
                    throw new ValidationException('Phase Validation Failed.', $workValidator->getErrors());
                }
                $totalPhasesBudget += (float) $phase['budget'] ?? 0.00;

                sanitizeData($phase);

                // Temporarily assign index as ID to avoid replacing other inserted fields in the container
                $phase['id'] = $index++;
                // Determine phase status
                $phase['status'] = WorkStatus::getStatusFromDates(
                    new DateTime($phase['startDateTime']),
                    new DateTime($phase['completionDateTime'])
                );

                // Create partial Phase entity and add to container
                $phasesContainer->add(Phase::createPartial($phase));
            }

            $phaseWorkerBudgetValidator = $workValidator->createBudgetBoundaryValidator($totalPhasesBudget);

            $workersContainer = new WorkerContainer();
            foreach ($workers as $worker) {
                $userValidator->validateDefaultRate($worker['defaultRate']);
                if ($userValidator->hasErrors()) {
                    throw new ValidationException('Worker Validation Failed.', $userValidator->getErrors());
                }

                $phaseWorkerBudgetValidator['addBudget']((float) $worker['defaultRate'] ?? DEFAULT_RATE_MIN);
                if ($workValidator->hasErrors()) {
                    throw new ValidationException('Phase Validation Failed.', $workValidator->getErrors());
                }

                $workersContainer->add(Worker::createPartial([
                    'publicId'      => UUID::fromString($worker['id']),
                    'defaultRate'   => floatval($worker['defaultRate']) ?? DEFAULT_RATE_MIN
                ]));
            }

            // Create partial Project entity
            $project['workers'] = $workersContainer;
            $project['phases'] = $phasesContainer;
            $project['status'] = WorkStatus::getStatusFromDates(
            new DateTime($project['startDateTime']), 
            new DateTime($project['completionDateTime']));
            $newProject = Project::createPartial($project);

            $newProject = ProjectService::create($newProject);

            Response::success([
                'projectId' => UUID::toString($newProject->getPublicId())
            ], 'Project created successfully.', 201);
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Project Creation Failed.', $e);
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
            self::formRateLimit();

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            if (!Role::isProjectManager(Me::getInstance()->getRole())) {
                throw new ForbiddenException('Only Project Managers can edit projects.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $project = ProjectModel::findFull($projectId, ['phases' => true]);
            if (!$project) {
                throw new NotFoundException('Project is not found.');
            }

            $workValidator = new WorkValidator();

            $projectData = ['id' => $project->getId()];

            if (isset($data['project']['name'])) {
                $projectData['name'] = $data['project']['name'];
            }

            if (isset($data['project']['description'])) {
                $projectData['description'] = $data['project']['description'];
            }

            if (isset($data['project']['budget'])) {
                $projectData['budget'] = floatval($data['project']['budget']) ?? 0.00;
            }

            if (isset($data['project']['maxWorkers'])) {
                $projectData['maxWorkers'] = $data['project']['maxWorkers'];
            }

            if (isset($data['project']['startDateTime'])) {
                $projectData['startDateTime'] = new DateTime($data['project']['startDateTime']);
            }

            if (isset($data['project']['completionDateTime'])) {
                $projectData['completionDateTime'] = new DateTime($data['project']['completionDateTime']);
            }

            if (isset($data['project']['status'])) {
                $projectData['status'] = WorkStatus::from($data['project']['status']);
            } elseif ($project->getStatus() !== WorkStatus::DELAYED) {
                $projectData['status'] = WorkStatus::getStatusFromDates(
                    $projectData['startDateTime'] ?? $project->getStartDateTime(),
                    $projectData['completionDateTime'] ?? $project->getCompletionDateTime()
                );
            }

            $phasesArray = $data['phases'] ?? [];
            foreach ($phasesArray as $key => &$arr) {
                foreach ($arr as &$value) {
                    $value['publicId'] = $value['id'];
                    unset($value['id']);

                    sanitizeData($value);

                    $existingPhase = null;
                    // Phase to edit / cancel - fetch existing phase for date bounds
                    if ($key === 'toEdit' || $key === 'toCancel') {
                        $existingPhase = PhaseModel::findById(UUID::fromString($value['publicId']));
                        if (!$existingPhase) {
                            throw new NotFoundException('Phase to edit not found.');
                        }
                    }

                    $startDateTime = isset($value['startDateTime'])
                        ? new DateTime($value['startDateTime'])
                        : $existingPhase->getStartDateTime();
                    $completionDateTime = isset($value['completionDateTime'])
                        ? new DateTime($value['completionDateTime'])
                        : $existingPhase->getCompletionDateTime();

                    if ($key === 'toAdd' || $key === 'toEdit') {
                        $workValidator->validateDateBounds(
                            $startDateTime,
                            $completionDateTime,
                            $projectData['startDateTime'] ?? $project->getStartDateTime(),
                            $projectData['completionDateTime'] ?? $project->getCompletionDateTime()
                        );
                        if ($workValidator->hasErrors()) {
                            throw new ValidationException('Phase Validation Failed.', $workValidator->getErrors());
                        }

                        $value['status'] = WorkStatus::getStatusFromDates($startDateTime, $completionDateTime);
                    }

                    if ($key === 'toCancel') {
                        $value['status'] = WorkStatus::CANCELLED;
                    }

                    $projectData['phases'][$key][] = $value;
                }
            }

            $instance = new self();

            $workersArray = $data['workers'] ?? [];
            foreach ($workersArray as $key => $arr) {
                foreach ($arr as $value) {
                    $value['publicId'] = $value['id'];
                    unset($value['id']);
                    if ($key === 'toRemove') {
                        $value['status'] = WorkerStatus::TERMINATED;
                    }
                    $projectData['workers'][$key][] = $value;
                }
            }

            // Save project edits
            if ($projectData && count($projectData) > 1) {
                $workValidator->validateMultiple($projectData);

                // Always compute totals from existing + requested changes so we can
                // correctly subtract cancelled phases / removed workers from budget validators.
                $phasesDelta = $projectData['phases'] ?? [];
                $workersDelta = $projectData['workers'] ?? [];

                // Validate project - phase budget bounds
                $totalPhasesBudget = $instance->getTotalPhaseBudget($project, $phasesDelta);
                $projectPhaseBudgetValidator = $workValidator->createBudgetBoundaryValidator(
                    $projectData['budget'] ?? $project->getBudget()
                );
                $projectPhaseBudgetValidator['addBudget']($totalPhasesBudget);

                // Validate phase - worker budget bounds
                if (!empty($workersDelta)) {
                    $totalDefaultRate = $instance->getTotalWorkerDefaultRate($project, $workersDelta);
                    $phaseWorkerBudgetValidator = $workValidator->createBudgetBoundaryValidator($totalPhasesBudget);
                    $phaseWorkerBudgetValidator['addBudget']($totalDefaultRate);
                }

                if ($workValidator->hasErrors()) {
                    throw new ValidationException('Project Validation Failed.', $workValidator->getErrors());
                }
                sanitizeData($projectData);

                ProjectService::save($projectData);
            }

            Response::success(['projectId' => UUID::toString($project->getPublicId())], 'Project edited successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Project Edit Failed.', $e);
        }
    }

    /**
     * Calculates the total budget for a project's phases, taking into account new and edited phases.
     *
     * This method merges the provided 'toAdd' and 'toEdit' entries from $phasesRaw, constructs
     * partial Phase objects for entries that include a 'budget' key, and then computes the
     * total budget by iterating the project's existing phases. For each existing phase, if a
     * corresponding new/edited phase exists it uses the new phase's budget; otherwise it uses
     * the existing phase's budget.
     *
     * Expected $phasesRaw structure:
     *      - toAdd: array[] Optional list of phase data to add
     *      - toEdit: array[] Optional list of phase data to edit
     * Only entries that contain a 'budget' key are considered when creating partial Phase objects.
     *
     * @param Project $project The project whose existing phases are considered
     * @param array $phasesRaw Associative array of raw phase data, typically containing
     *      - 'toAdd': array List of phases to add
     *      - 'toEdit': array List of phases to edit
     *
     * @return float The summed budget of all phases after applying additions/edits
     */
    private function getTotalPhaseBudget(Project $project, array $phasesRaw): float
    {
        // Build overrides lookup
        $overrides = [];

        // Phases that are being cancelled in this edit operation
        $cancelled = [];
        foreach (($phasesRaw['toCancel'] ?? []) as $phaseRaw) {
            if (!isset($phaseRaw['publicId'])) {
                continue;
            }
            $cancelled[$phaseRaw['publicId']] = true;
        }

        $merged = array_merge(
            $phasesRaw['toAdd'] ?? [],
            $phasesRaw['toEdit'] ?? []
        );

        foreach ($merged as $phaseRaw) {
            if (!isset($phaseRaw['publicId'], $phaseRaw['budget'])) {
                continue;
            }

            $overrides[$phaseRaw['publicId']] = (float) $phaseRaw['budget'];
        }

        // Sum budgets of existing phases (with overrides)
        $total = 0.0;
        $existingIds = [];

        foreach ($project->getPhases() as $phase) {
            $id = UUID::toString($phase->getPublicId());
            $existingIds[] = $id;

            // Exclude cancelled phases from the total (both already-cancelled and newly-cancelled)
            if (($cancelled[$id] ?? false) || $phase->getStatus() === WorkStatus::CANCELLED) {
                continue;
            }

            $total += $overrides[$id] ?? $phase->getBudget();
        }

        // Add budgets of newly added phases
        foreach ($overrides as $id => $budget) {
            if (!in_array($id, $existingIds, true)) {
                $total += $budget;
            }
        }

        return $total;
    }


    /**
     * Calculate the total default rate for a project's workers, taking into account
     * new or edited worker entries provided in $workersRaw.
     *
     * This method builds a temporary WorkerContainer from the merged 'toAdd' and
     * 'toEdit' entries in $workersRaw (only entries that include a 'defaultRate'
     * are converted via Worker::createPartial). It then iterates the project's
     * existing workers and, for each worker, uses the updated worker's defaultRate
     * when an updated entry exists (matched by worker ID), or the existing worker's
     * defaultRate otherwise. The sum of these rates is returned.
     *
     * @param Project $project The project whose existing workers are considered
     * @param array $workersRaw Associative array possibly containing:
     *      - 'toAdd': array List of worker data arrays to add (may include 'defaultRate')
     *      - 'toEdit': array List of worker data arrays to edit (may include 'defaultRate')
     *      Only entries with a 'defaultRate' key are considered for overrides.
     *
     * @return float The summed default rate of the project's workers after applying
     *               additions/edits (as a float).
     */
    private function getTotalWorkerDefaultRate(Project $project, array $workersRaw): float
    {
        // Build overrides lookup
        $overrides = [];

        // Workers that are being removed (terminated) in this edit operation
        $terminated = [];
        foreach (($workersRaw['toRemove'] ?? []) as $workerRaw) {
            if (!isset($workerRaw['publicId'])) {
                continue;
            }
            $terminated[$workerRaw['publicId']] = true;
        }

        $merged = array_merge(
            $workersRaw['toAdd'] ?? [],
            $workersRaw['toEdit'] ?? []
        );

        foreach ($merged as $workersRaw) {
            if (!isset($workersRaw['publicId'], $workersRaw['defaultRate'])) {
                continue;
            }

            $overrides[$workersRaw['publicId']] = (float) $workersRaw['defaultRate'];
        }

        // Sum budgets of existing workers (with overrides)
        $total = 0.0;
        $existingIds = [];

        foreach ($project->getWorkers() as $worker) {
            $id = UUID::toString($worker->getPublicId());
            $existingIds[] = $id;

            // Exclude terminated workers from the total (both already-terminated and newly-terminated)
            if (($terminated[$id] ?? false) || $worker->getStatus() === WorkerStatus::TERMINATED) {
                continue;
            }

            $total += $overrides[$id] ?? $worker->getDefaultRate();
        }

        // Add budgets of newly added workers
        foreach ($overrides as $id => $budget) {
            if (!in_array($id, $existingIds, true)) {
                $total += $budget;
            }
        }

        return $total;
    }

    /**
     * Not implemented (No use case)
     */
    public static function getById(array $args = []): void
    {
    }

    /**
     * Not implemented (No use case)
     */
    public static function delete(array $args = []): void
    {
    }
}