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
use App\Exception\ValidationException;
use App\Interface\Controller;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Utility\ProjectProgressCalculator;
use App\Validator\UuidValidator;
use InvalidArgumentException;
use DateTime;
use Exception;

class ProjectController implements Controller
{
    private UuidValidator $uuidValidator;

    private function __construct()
    {
        $this->uuidValidator = new UuidValidator();
    }

    public static function index(): void
    {
    }

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
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            $instance = new self();

            $fullProjectInfo = null;
            $activeProject = (Role::isProjectManager(Me::getInstance()))
                ? ProjectModel::findManagerActiveProjectByManagerId(Me::getInstance()->getId())
                : ProjectModel::findWorkerActiveProjectByWorkerId(Me::getInstance()->getId());

            // If projectId is provided, verify that the project is not cancelled
            if ($activeProject && $activeProject->getStatus() !== WorkStatus::CANCELLED) {
                $fullProjectInfo = $instance->getProjectInfo($activeProject->getPublicId());
                $projectId = $fullProjectInfo ? UUID::toString($fullProjectInfo->getPublicId()) : null;
                if ($projectId && !Session::has('activeProjectId')) {
                    Session::set('activeProjectId', $projectId);
                }

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
     * Updates the status of each phase in a project based on their dates and progress.
     *
     * This method performs the following actions:
     * - Retrieves all phases associated with the given project.
     * - Throws NotFoundException if no phases are found.
     * - Calculates project progress and iterates through each phase's breakdown.
     * - Adds phases and their tasks to the project object.
     * - Updates phase status according to the following rules:
     *      - PENDING → ON_GOING: If the phase's start date has passed.
     *      - ON_GOING → COMPLETED: If the phase's completion date has passed and progress is 100%.
     *      - ON_GOING → DELAYED: If the phase's completion date has passed and progress is less than 100%.
     * - Collects phases that require status updates and persists changes to the database.
     * - Adds calculated progress information as additional info to the project.
     *
     * @param Project $project Reference to the project object whose phases will be updated.
     *      The method will add phases, tasks, and progress info to this object.
     *
     * @throws NotFoundException If no phases are found for the given project.
     *
     * @return void
     */
    private function updatePhaseStatus(Project &$project): void
    {
        $phases = PhaseModel::findAllByProjectId($project->getId(), true);
        if (!$phases) {
            throw new NotFoundException('Phases not found.');
        }

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
                $project->addTask($task);
            }

            // Update phase status based on dates and progress
            // Transition: PENDING → ON_GOING (when start date has passed)
            if ($status === WorkStatus::PENDING && compareDates($startDateTime, $now) < 0) {
                $reference->setStatus(WorkStatus::ON_GOING);
                $phasesToUpdate[] = [
                    'id' => (int) $key,
                    'status' => WorkStatus::ON_GOING
                ];
            } 
            // Transition: ON_GOING → COMPLETED or DELAYED (when completion date has passed)
            elseif ($status === WorkStatus::ON_GOING && compareDates($completionDateTime, $now) < 0) {
                if ($value['simpleProgress'] >= 100.0) {
                    $reference->setStatus(WorkStatus::COMPLETED);
                    $phasesToUpdate[] = [
                        'id' => (int) $key,
                        'status' => WorkStatus::COMPLETED
                    ];
                } else {
                    $reference->setStatus(WorkStatus::DELAYED);
                    $phasesToUpdate[] = [
                        'id' => (int) $key,
                        'status' => WorkStatus::DELAYED
                    ];
                }
            }
        }

        // Update phase status in the database
        if (!empty($phasesToUpdate)) {
            PhaseModel::saveMultiple($phasesToUpdate);
        }

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
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $fullProjectInfo = $instance->getProjectInfo(
                $projectId,
                [
                    'tasks' => true,
                    'workers' => true
                ]
            );
            if (!$fullProjectInfo) {
                throw new NotFoundException('Project not found.');
            }

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
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            $key = '';
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $key = trim($_GET['key']);
            }

            // Only status can be filtered here
            $status = isset($_GET['filter']) && strcasecmp($_GET['filter'], 'all') !== 0
                ? WorkStatus::from($_GET['filter'])
                : null;

            $projects = ProjectModel::search(
                $key,
                Me::getInstance()->getId(),
                $status,
                [
                    'limit' => 50,
                    'offset' => 0
                ]
            );

            require_once VIEW_PATH . 'projects.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    /**
     * Retrieves detailed information about a project by its UUID.
     *
     * This method fetches a project along with its related data, including phases, tasks, and workers.
     * If the provided project ID is null, the method returns null.
     *
     * @param UUID|null $projectId The unique identifier of the project to retrieve, or null.
     * 
     * @return Project|null The Project instance with full details (phases, tasks, workers), or null if no ID is provided.
     */
    private function getProjectInfo(
        UUID|null $projectId,
        array $options = [
            'phases' => false,
            'workers' => false,
            'tasks' => false
        ]
    ): ?Project {
        if (!$projectId) {
            return null;
        }

        $includeTasks = $options['tasks'] ?? false;
        $includePhases = $options['phases'] ?? false;
        $includeWorkers = $options['workers'] ?? false;

        return ProjectModel::findFull($projectId, [
            'phases' => $includePhases,
            'tasks' => $includeTasks,
            'workers' => $includeWorkers
        ]);
    }

    /**
     * Renders the project dashboard and updates project status based on current date and task statuses.
     *
     * This method performs the following actions:
     * - Checks if a project is provided.
     * - If the project start date has passed and the status is PENDING, updates the status to ON_GOING.
     * - If the completion date has passed and the status is PENDING or ON_GOING:
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
                $phases = PhaseModel::findAllByProjectId($project->getId(), true);
                $projectProgress = ($phases?->count() > 0)
                    ? ProjectProgressCalculator::calculate($phases)
                    : [
                        'progressPercentage' => 0.0,
                        'statusBreakdown' => [],
                        'priorityBreakdown' => [],
                        'phaseBreakdown' => []
                    ];
            }

            if ($startDateTime && compareDates($currentDateTime, $startDateTime) >= 0 && $status === WorkStatus::PENDING) {
                // Check if the project is already ongoing
                $project->setStatus(WorkStatus::ON_GOING);
                ProjectModel::save([
                    'id' => $project->getId(),
                    'status' => WorkStatus::ON_GOING
                ]);
            } elseif (
                $completionDateTime && compareDates($completionDateTime, $currentDateTime) < 0 &&
                ($status === WorkStatus::PENDING || $status === WorkStatus::ON_GOING)
            ) {
                if ($projectProgress['progressPercentage'] < 100.0) {
                    $project->setStatus(WorkStatus::DELAYED);
                    ProjectModel::save([
                        'id' => $project->getId(),
                        'status' => WorkStatus::DELAYED
                    ]);
                } else {
                    $project->setStatus(WorkStatus::COMPLETED);
                }
            }
        }

        require_once VIEW_PATH . 'home.php';
    }

    public static function viewReport(array $args = []): void 
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            $instance = new self();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!$projectId) {
                throw new NotFoundException('Project ID is required.');
            }

            $projectReport = ProjectModel::getReport($projectId);

            require_once SUB_VIEW_PATH . 'report.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }
}
