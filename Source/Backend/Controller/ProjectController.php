<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Core\Me;
use App\Core\Session;
use App\Core\UUID;
use App\Entity\Project;
use App\Enumeration\Role;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use App\Service\PhaseService;
use App\Service\ProjectService;
use App\Utility\ProjectProgressCalculator;
use App\Utility\TemporaryId;
use App\Validator\UuidValidator;
use DateTime;

class ProjectController implements Controller
{
    private ProjectModel $projectModel;
    private PhaseModel $phaseModel;
    private TaskModel $taskModel;

    private PhaseService $phaseService;

    private UuidValidator $uuidValidator;

    /**
     * Initializes the controller's internal dependencies.
     *
     * This private constructor:
     * - Prevents direct external instantiation of the controller (private visibility).
     * - Instantiates and assigns a UuidValidator to $this->uuidValidator for UUID validation tasks.
     *
     * Instances of this class should be created via the class factory or designated creation methods.
     *
     * @internal Used for internal initialization only.
     * @return void
     */
    private function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->phaseModel = new PhaseModel();
        $this->taskModel = new TaskModel();

        $this->phaseService = new PhaseService();

        $this->uuidValidator = new UuidValidator();
    }

    public static function index(): void {}

    /**
     * Displays the active project dashboard for the currently authenticated user.
     *
     * This method checks if the user has an authorized session and determines the user's active project
     * based on their role (project manager or worker). If an active project is found and is not cancelled,
     * it retrieves the full project information and sets the active project ID in the session if not already set.
     * Finally, it renders the dashboard with the project information.
     *
     * Handles forbidden and not found exceptions by delegating to the appropriate error controller methods.
     *
     * @throws ForbiddenException If the user does not have an authorized session or access is denied.
     * @throws NotFoundException If the requested resource is not found.
     *
     * @return void
     */
    public static function viewHome(): void
    {
        try {
            SessionAuth::redirectIfNotAuthorized();

            $instance = new self();

            $fullProjectInfo = null;
            $activeProject = (Role::isProjectManager(Me::getInstance()->getRole()))
                ? $instance->projectModel->findManagerActiveProjectByManagerId(Me::getInstance()->getId())
                : $instance->projectModel->findWorkerActiveProjectByWorkerId(Me::getInstance()->getId());

            // If projectId is provided, verify that the project is not cancelled
            if ($activeProject && $activeProject->getStatus() !== WorkStatus::CANCELLED) {
                $fullProjectInfo = $instance->getProjectInfo($activeProject->getPublicId(), [
                    'workers' => true,
                ]);
                $projectId = $fullProjectInfo ? UUID::toString($fullProjectInfo->getPublicId()) : null;
                if ($projectId && !Session::has('activeProjectId'))
                    Session::set('activeProjectId', $projectId);

                $instance->updatePhaseStatus($fullProjectInfo);
            }

            $instance->renderDashboard($fullProjectInfo);
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    /**
     * Updates the statuses of all phases for the given project based on dates and progress.
     *
     * This method performs the following steps:
     * - Loads all phases for the provided project (via PhaseModel::findAllByProjectId)
     *   and throws NotFoundException if no phases are found.
     * - Calculates project progress using ProjectProgressCalculator::calculate and
     *   iterates the returned 'phaseBreakdown' to determine per-phase progress.
     * - For each phase it:
     *   - Normalizes dates using formatDateTime(..., 'Y-m-d') and compares them to the current date.
     *   - Adds the phase and its tasks into the provided Project object.
     *   - Applies status transitions using WorkStatus constants:
     *     - PENDING → ONGOING when the phase start date is on or before today.
     *     - ONGOING or DELAYED → COMPLETED when the phase completion date has passed and
     *       the phase simpleProgress is >= 100.0.
     *     - ONGOING or DELAYED → DELAYED when the phase completion date has passed and
     *       the phase simpleProgress is < 100.0.
     *   - Collects phases that require a persistent status update in a container suitable
     *     for PhaseModel::saveMultiple.
     * - Persists status updates to the database by calling PhaseModel::saveMultiple when needed.
     * - Adds the full project progress information to the Project as additional info under key 'progress'.
     *
     * Notes on data shapes and side effects:
     * - The ProjectProgressCalculator::calculate result is expected to contain a 'phaseBreakdown'
     *   array keyed by the same keys/IDs used to index the $phases collection, with each value
     *   containing at least 'simpleProgress' (float).
     * - The $phases collection items are expected to provide getStartDateTime(), getCompletionDateTime(),
     *   getStatus(), getTasks(), setStatus(), and be addable to the Project via Project::addPhase.
     * - The container of updates passed to PhaseModel::saveMultiple is an array of associative arrays:
     *     - id: int (phase identifier)
     *     - status: string (WorkStatus constant)
     *
     * @param Project &$project Project instance to update (phases, tasks and additional info will be modified).
     *
     * @throws NotFoundException If no phases are found for the project.
     *
     * @return void
     */
    private function updatePhaseStatus(Project &$project): void
    {
        $instance = new self();

        $projectId = TemporaryId::isTemporary($project->getId())
            ? $project->getPublicId()
            : $project->getId();
        $phases = $this->phaseService->getByProjectId($projectId, ['tasks' => true]);
        if (!$phases) throw new NotFoundException('Phases not found');

        // Container of phase IDs to update status
        $phasesToUpdate = [];

        $now = formatDateTime(new DateTime(), 'Y-m-d');

        $projectProgress = ProjectProgressCalculator::calculate($phases);
        foreach ($projectProgress['phaseBreakdown'] as $key => $value) {
            $reference = $phases->get((int) $key);

            $tasks = $reference->getTasks();
            $status = $reference->getStatus();
            $startDateTime = formatDateTime($reference->getStartDateTime(), 'Y-m-d');
            $completionDateTime = formatDateTime($reference->getCompletionDateTime(), 'Y-m-d');

            // Add phase and tasks into the project object
            $project->addPhase($reference);
            foreach ($tasks as $task) {
                $reference->addTask($task);
            }

            // Update phase status based on dates and progress
            // Transition: PENDING → ONGOING (when start date has passed)
            if ($status === WorkStatus::PENDING && compareDates($startDateTime, $now) <= 0) {
                $reference->setStatus(WorkStatus::ONGOING);
                $phasesToUpdate[] = [
                    'id'     => (int) $key,
                    'status' => WorkStatus::ONGOING
                ];
            }
            // Transition: ONGOING → COMPLETED or DELAYED (when completion date has passed)
            elseif (
                ($status === WorkStatus::ONGOING
                    || $status === WorkStatus::DELAYED)
                && compareDates($completionDateTime, $now) < 0
            ) {
                if ($value['simpleProgress'] >= 100.0) {
                    $reference->setStatus(WorkStatus::COMPLETED);
                    $phasesToUpdate[] = [
                        'id'     => (int) $key,
                        'status' => WorkStatus::COMPLETED
                    ];
                } else {
                    $reference->setStatus(WorkStatus::DELAYED);
                    $phasesToUpdate[] = [
                        'id'     => (int) $key,
                        'status' => WorkStatus::DELAYED
                    ];
                }
            }
        }

        // Update phase status in the database
        if (!empty($phasesToUpdate))
            $instance->phaseModel->save($phasesToUpdate);

        // Set additional info on project
        $project->addAdditionalInfo('progress', $projectProgress);
    }

    /**
     * Displays the dashboard view for a specific project for authorized users.
     *
     * This static method checks if the current session is authorized, retrieves the project information
     * based on the provided project ID, and renders the dashboard for that project. If the project is not found
     * or the user is not authorized, appropriate error handlers are invoked.
     *
     * @param array $args Optional associative array of arguments:
     *      - projectId: string|null The UUID string of the project to view.
     *
     * @throws NotFoundException If the project is not found.
     * @throws ForbiddenException If the user is not authorized to view the project.
     *
     * @return void
     */
    public static function viewOther(array $args = []): void
    {
        try {
            SessionAuth::redirectIfNotAuthorized();

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) throw new ForbiddenException('Project ID is required');

            $fullProjectInfo = $instance->getProjectInfo(
                $projectId,
                [
                    'tasks'     => true,
                    'workers'   => true
                ]
            );
            if (!$fullProjectInfo) throw new NotFoundException('Project not found');

            $instance->renderDashboard($fullProjectInfo);
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    /**
     * Displays the project grid view for the currently authenticated user.
     *
     * This method checks for an authorized session and retrieves a list of projects
     * for the current user, supporting optional filtering by project status and search key.
     * - If a search key is provided via the 'key' GET parameter, it performs a search for projects
     *   matching the key, limited to 50 results.
     * - If no search key is provided, it fetches projects based on the user's role:
     *   - Project managers see projects they manage.
     *   - Other users see projects they are assigned to as workers.
     * - The 'filter' GET parameter can be used to filter projects by status; if set to 'all' or omitted,
     *   no status filter is applied.
     * - Handles forbidden and not found exceptions by delegating to the appropriate error controller methods.
     *
     * @throws ForbiddenException If the user does not have an authorized session.
     * @throws NotFoundException If the requested resource is not found.
     *
     * @return void
     */
    public static function viewGrid(): void
    {
        try {
            SessionAuth::redirectIfNotAuthorized();

            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '')
                $key = trim($_GET['key']);

            // Only status can be filtered here
            $status = isset($_GET['status']) && strcasecmp($_GET['status'], 'all') !== 0
                ? WorkStatus::from($_GET['status'])
                : null;

            $instance = new self();
            $projects = $instance->projectModel->search(
                $key,
                [
                    'userId'    => Me::getInstance()->getId(),
                    'status'    => $status,

                    'limit'     => 50,
                    'offset'    => 0
                ]
            );

            require_once VIEW_PATH . 'Projects.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    /**
     * Retrieves full project information including optional related entities.
     *
     * This private method fetches a Project by its public ID and can include related
     * entities such as phases, workers, and tasks based on the provided options.
     * Additionally, it retrieves the three most recent tasks associated with the project
     * and adds them as additional info to the Project object.
     *
     * @param UUID|null $projectId The UUID of the project to retrieve.
     * @param array $options Optional associative array to specify related entities to include:
     *      - 'phases' => bool Whether to include phases (default: false)
     *      - 'workers' => bool Whether to include workers (default: false)
     *      - 'tasks' => bool Whether to include tasks (default: false)
     *
     * @return Project|null The Project instance with requested related entities, or null if not found.
     */
    private function getProjectInfo(
        UUID|null $projectId,
        array $options = [
            'phases' => false,
            'workers' => false,
            'tasks' => false
        ]
    ): ?Project {
        if (!$projectId) return null;

        $includeTasks = $options['tasks'] ?? false;
        $includePhases = $options['phases'] ?? false;
        $includeWorkers = $options['workers'] ?? false;

        $instance = new self();

        $projectService = new ProjectService();
        $project = $projectService->get($projectId, [
            'phases'    => $includePhases,
            'tasks'     => $includeTasks,
            'workers'   => $includeWorkers
        ]);

        $recentTasks = $instance->taskModel->search(
            '',
            [
                'projectId' => $projectId,
                'limit'     => 3,
                'offset'    => 0
            ]
        );
        if ($recentTasks) $project->addAdditionalInfo('recentTasks', $recentTasks);

        return $project;
    }

    /**
     * Renders the project dashboard and updates project status based on current date and task statuses.
     *
     * This method performs the following actions:
     * - Checks if a project is provided.
     * - If the project start date has passed and the status is PENDING, updates the status to ONGOING.
     * - If the completion date has passed and the status is PENDING or ONGOING:
     *      - Checks if there are any tasks not completed or cancelled.
     *      - If there are pending tasks, updates the project status to DELAYED.
     *      - If all tasks are completed or cancelled, updates the project status to COMPLETED.
     * - Saves status changes to the database using ProjectModel.
     * - Loads the dashboard view.
     *
     * @param Project|null $project The project instance to render the dashboard for, or null if not available.
     *
     * @return void
     */
    private function renderDashboard(Project|null $project): void
    {
        $instance = new self();
        $projectProgress = null;

        if ($project) {
            $status = $project->getStatus();
            $startDateTime = formatDateTime($project->getStartDateTime(), 'Y-m-d');
            $completionDateTime = formatDateTime($project->getCompletionDateTime(), 'Y-m-d');
            $currentDateTime = formatDateTime(new DateTime(), 'Y-m-d');

            // Determine project progress
            if ($project->additionalInfoContains('progress')) {
                $projectProgress = $project->getAdditionalInfo('progress');
            } else {
                $phases = $this->phaseService->getByProjectId($project->getId(), ['tasks' => true]);
                $projectProgress = ($phases?->count() > 0)
                    ? ProjectProgressCalculator::calculate($phases)
                    : [
                        'progressPercentage' => 0.0,
                        'statusBreakdown'    => [],
                        'priorityBreakdown'  => [],
                        'phaseBreakdown'     => []
                    ];
                $project->setPhases($phases);
            }

            if ($startDateTime && compareDates($currentDateTime, $startDateTime) >= 0 && $status === WorkStatus::PENDING) {
                // Check if the project is already ongoing
                $project->setStatus(WorkStatus::ONGOING);
                $instance->projectModel->save([
                    'id'        => $project->getId(),
                    'status'    => WorkStatus::ONGOING
                ]);
            } elseif (
                $completionDateTime && compareDates($completionDateTime, $currentDateTime) < 0 &&
                ($status === WorkStatus::PENDING || $status === WorkStatus::ONGOING || $status === WorkStatus::DELAYED)
            ) {
                if ($projectProgress['progressPercentage'] < 100.0) {
                    $project->setStatus(WorkStatus::DELAYED);
                    $instance->projectModel->save([
                        'id'        => $project->getId(),
                        'status'    => WorkStatus::DELAYED
                    ]);
                } else {
                    $project->setStatus(WorkStatus::COMPLETED);
                    $instance->projectModel->save([
                        'id'        => $project->getId(),
                        'status'    => WorkStatus::COMPLETED
                    ]);
                }
            }
        }

        require_once VIEW_PATH . 'Home.php';
    }

    public static function viewReport(array $args = []): void
    {
        try {
            SessionAuth::redirectIfNotAuthorized();

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) throw new NotFoundException('Project ID is required');

            $projectReport = $instance->projectModel->getReport($projectId);

            require_once SUB_VIEW_PATH . 'Report.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    /**
     * Displays the project creation/edit form view.
     *
     * This method checks for an authorized session before rendering the form.
     * If a projectId is provided in the arguments, it retrieves the existing
     * project for editing; otherwise, it prepares the form for creating a new project.
     *
     * @param array $args Optional associative array of arguments:
     *      - projectId: string UUID of the project to edit (optional)
     *
     * @return void
     *
     * @throws NotFoundException Redirects to 404 error page if the project is not found
     * @throws ForbiddenException Redirects to 403 error page if access is denied
     */
    public static function viewProjectForm(array $args = []): void
    {
        try {
            SessionAuth::redirectIfNotAuthorized();
            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            $project = isset($projectId)
                ? $instance->getProjectInfo($projectId, [
                    'phases'    => true,
                    'workers'   => true,
                ])
                : null;

            require_once SUB_VIEW_PATH . 'Form' . DS . 'Project.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }
}
