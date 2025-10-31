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

    public static function viewHomeProject(): void
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
        
            // If projectId is provided, verify that the user works on the project and the project is not cancelled
            if (
                $activeProject &&
                // ProjectWorkerModel::worksOn($activeProject->getId(),Me::getInstance()->getId()) &&
                $activeProject->getStatus() !== WorkStatus::CANCELLED
            ) {
                $fullProjectInfo = $instance->getProjectInfo($activeProject->getPublicId());
                $projectId = $fullProjectInfo ? UUID::toString($fullProjectInfo->getPublicId()) : null;
                if ($projectId && !Session::has('activeProjectId')) {
                    Session::set('activeProjectId', $projectId);
                }
            }

            $instance->renderDashboard($fullProjectInfo);
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (Exception $e) {
            ErrorController::forbidden();
        }
    }

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
            } elseif ($completionDateTime && $currentDateTime > $completionDateTime && ($status === WorkStatus::PENDING || $status === WorkStatus::ON_GOING)) {
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
