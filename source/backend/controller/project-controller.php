<?php

namespace App\Controller;

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
            $projectId = $args['projectId'] ?? null;

        } catch (ValidationException $e) {
            //throw $th;
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
