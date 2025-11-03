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
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
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
     * Displays the home project dashboard for the currently authenticated user.
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
                throw new ForbiddenException();
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
            }

            $instance->renderDashboard($fullProjectInfo);
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
    private function getProjectInfo(UUID|null $projectId): ?Project
    {
        if (!$projectId) {
            return null;
        }
        
        return ProjectModel::findFull($projectId, [
            'phases' => true,
            'tasks' => true,
            'workers' => true
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
        if ($project) {
            $status = $project->getStatus();
            $startDateTime = $project->getStartDateTime();
            $completionDateTime = $project->getCompletionDateTime();
            $currentDateTime = new DateTime();

            if ($startDateTime && $currentDateTime >= $startDateTime && $status === WorkStatus::PENDING) {
                // Check if the project is already ongoing
                $project->setStatus(WorkStatus::ON_GOING);
                ProjectModel::save([
                    'id' => $project->getId(),
                    'status' => WorkStatus::ON_GOING
                ]);
            } elseif ($completionDateTime && $currentDateTime > $completionDateTime && 
                    ($status === WorkStatus::PENDING || $status === WorkStatus::ON_GOING)) {
                // Check if the project is already completed or delayed based on current date and tasks status
                $hasPendingTasks = false;
                foreach ($project->getTasks() as $task) {
                    if ($task->getStatus() !== WorkStatus::COMPLETED && $task->getStatus() !== WorkStatus::CANCELLED) {
                        $hasPendingTasks = true;
                        break;
                    }
                }
                if ($hasPendingTasks) {
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

    public static function viewProjectGrid(): void
    {
        $projects = ProjectModel::all();

        require_once VIEW_PATH . 'projects.php';
    }

    // 
}
