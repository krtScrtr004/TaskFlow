<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Core\Me;
use App\Core\UUID;
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

    public static function viewHomeProject(array $args = []): void
    {
        if (!SessionAuth::hasAuthorizedSession()) {
            ErrorController::forbidden();
        }

        $projectId = isset($args['projectId']) 
            ? UUID::fromString($args['projectId']) 
            : null;
        
        if ($projectId) {
            try {
                if (ProjectWorkerModel::worksOn(
                    $projectId,
                    Me::getInstance()->getId())) {
                        header('Location: ' . REDIRECT_PATH . 'home');
                    }
            } catch (Exception $e) {
                ErrorController::forbidden();
                exit();
            }
        }

        $instance = new self();
        $instance->renderDashboard($projectId);
    }

    private function renderDashboard(UUID|null $projectId): void {
        try {
            if ($projectId) {
                $instance = new self();
                $instance->uuidValidator->validateUuid($projectId);
                if ($instance->uuidValidator->hasErrors()) {
                    throw new ValidationException(
                        'Invalid project ID.',
                        $instance->uuidValidator->getErrors()
                    );
                }
                $project = ProjectModel::findFull($projectId, [
                    'phases' => true,
                    'tasks'  => true,
                    'workers' => true
                ]);

                // Check if the project is already completed or delayed based on current date and tasks status
                $completionDateTime = $project->getCompletionDateTime();
                $currentDateTime = new DateTime();
                if ($completionDateTime && $currentDateTime > $completionDateTime) {
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
                            'id'        => $project->getId(),
                            'status'    => WorkStatus::DELAYED
                        ]);
                    } else {
                        $project->setStatus(WorkStatus::COMPLETED);
                    }
                }
            }

            require_once VIEW_PATH . 'home.php';
        } catch (ValidationException $e) {
            ErrorController::notFound();
        }
    }

    public static function viewProjectGrid(): void
    {
        $projects = ProjectModel::all();

        require_once VIEW_PATH . 'projects.php';
    }

    // 
}
