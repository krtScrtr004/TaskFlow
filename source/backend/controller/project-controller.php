<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Interface\Controller;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Validator\UuidValidator;
use InvalidArgumentException;
use DateTime;

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

    public static function viewProject(array $args = []): void
    {
        $instance = new self();
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('Unauthorized access.');
            }

            $project = null;

            $projectId = isset($args['projectId']) ? UUID::fromString($args['projectId']) : null;
            if ($projectId) {
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
                        $project->save([
                            'id'        => $project->getId(),
                            'status'    => WorkStatus::DELAYED
                        ]);
                    } else {
                        $project->setStatus(WorkStatus::COMPLETED);
                    }
                }
            }

            require_once VIEW_PATH . 'home.php';
        } catch (ValidationException | NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }

    public static function viewProjectGrid(): void
    {
        $projects = ProjectModel::all();

        require_once VIEW_PATH . 'projects.php';
    }

    // 
}
