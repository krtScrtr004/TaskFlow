<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Validator\UuidValidator;
use InvalidArgumentException;

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

            $projectId = isset($args['projectId']) ? UUID::fromString($args['projectId']) : null;
            if ($projectId) {
                $instance->uuidValidator->validateUuid($projectId);
                if ($instance->uuidValidator->hasErrors()) {
                    throw new ValidationException(
                        'Invalid project ID.',
                        $instance->uuidValidator->getErrors()
                    );
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
