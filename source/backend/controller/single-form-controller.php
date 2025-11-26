<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Core\Session;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;
use App\Middleware\Csrf;
use App\Model\PhaseModel;
use App\Model\ProjectModel;

class SingleFormController implements Controller
{
    private array $components = [
        'resetPassword' => [
            'title' => 'Reset Your Password',
            'description' => 'Enter your email address below and we will send you a link to reset your password.',
            'form' => 'resetPassword',
            'script' => ['single-form/reset-password/send-link']
        ],
        'changePassword' => [
            'title' => 'Set Your Password',
            'description' => 'Create a new password.',
            'form' => 'changePassword',
            'script' => [
                'password-list-validator',
                'single-form/change-password/submit'
            ]
        ],
        'createProject' => [
            'title' => 'Create New Project',
            'description' => 'Fill in the details below to create a new project.',
            'form' => 'createProject',
            'script' => [
                'single-form/project/open-phase',
                'single-form/project/create/add-phase',
                'single-form/project/create/cancel-phase',
                'single-form/project/create/submit',
            ]
        ],
        'editProject' => [
            'title' => 'Edit Project Details',
            'description' => 'Modify the details of your project below.',
            'form' => 'editProject',
            'script' => [
                'single-form/project/open-phase',
                'single-form/project/edit/cancel-phase',
                'single-form/project/edit/add-phase',
                'single-form/project/edit/submit',
            ],
        ],
        'addTask' => [
            'title' => '',
            'description' => 'Fill in the details below to add a new task.',
            'form' => 'addTask',
            'script' => [
                'add-worker-modal/task/new/add',
                'add-worker-modal/task/new/open',
                'single-form/add-task/submit',
            ]
        ]
    ];

    private function __construct()
    {
    }

    /**
     * Handles requests for single-form pages and renders the corresponding view.
     *
     * This static controller action performs request routing, session/CSRF initialization,
     * authorization checks, and special-case handling for certain pages before including
     * the single-form view. Key responsibilities:
     * - Ensures a session exists for unauthenticated users and generates a CSRF token if missing.
     *   Forces session write/close then restores the session to persist the token.
     * - Parses the request URI ($_SERVER['REQUEST_URI']) to determine the requested page:
     *   converts the third URI segment from kebab-case to camelCase and falls back to 'forgetPassword'.
     * - Resolves the page's component configuration to determine scripts and the form file path.
     * - Enforces authorization for protected pages (createPassword, editProject, addTask):
     *   redirects unauthenticated users to REDIRECT_PATH . 'login' and exits.
     * - Special handling:
     *   - addTask: accepts an optional projectId via $args, calls instance->addTask($projectId),
     *     obtains $project and $activePhase, includes VIEW_PATH . 'single-form.php' and returns.
     *   - changePassword: validates a token passed via $_GET['token'] and throws a ForbiddenException
     *     if the token is missing or invalid.
     * - Loads the view (VIEW_PATH . 'single-form.php') for normal flow.
     * - Catches ForbiddenException and NotFoundException and delegates to ErrorController::forbidden()
     *   and ErrorController::notFound(), respectively.
     *
     * Notes / side effects:
     * - May send HTTP redirects and call exit().
     * - Requires and includes view files; may output content.
     * - Reads superglobals: $_SERVER['REQUEST_URI'], $_GET['token'].
     * - Depends on Session, Csrf, SessionAuth, and various application constants (VIEW_PATH, REDIRECT_PATH, DS).
     *
     * @param array $args Associative array of optional arguments. Supported keys:
     *      - projectId: int|string (optional) Project identifier used when handling the 'addTask' page.
     *
     * @return void
     */
    public static function index(array $args = []): void
    {
        // For unauthenticated users, ensure session exists and CSRF token is set
        if (!Session::isSet()) {
            Session::create();
        }

        if (!Csrf::get()) {
            Csrf::generate();
            // Force session write to ensure CSRF token is persisted
            session_write_close();
            Session::restore();  // Reopen session for the rest of the request
        }

        $instance = new self();
        $components = $instance->components;

        $uriParts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $path = $uriParts[0];
        $segments = explode('/', $path);
        $page = kebabToCamelCase($segments[2] ?? '') ?: 'forgetPassword';
        $component = $components[$page];

        $scripts = $component['script'];
        $form = 'single-form' . DS . camelToKebabCase($component['form']) . '.php';

        try {
            // Ensure user is authorized for protected pages
            if (array_key_exists($page, ['createPassword', 'editProject', 'addTask'])) {
                if (!SessionAuth::hasAuthorizedSession()) {
                    header('Location: ' . REDIRECT_PATH . 'login');
                    exit();
                }
            }

            // Special handling for certain pages
            if ($page === 'addTask') {
                // Handle add task separately to fetch project and active phase
                $projectId = $args['projectId'] ?? null;
                [$project, $activePhase] = $instance->addTask($projectId);

                require_once VIEW_PATH . 'single-form.php';
                return;
            } elseif ($page === 'changePassword') {
                // Check token validity for change password page
                $token = $_GET['token'];
                if (!$token || !isset($token) || !trimOrNull($token)) {
                    throw new ForbiddenException('Token not provided.');
                }
            }

            require_once VIEW_PATH . 'single-form.php';
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        }
    }

        /**
     * Retrieves the project and its active phase for adding a new task.
     *
     * This method performs the following steps:
     * - Validates the provided project ID.
     * - Finds the project by its UUID.
     * - Retrieves the ongoing (active) phase associated with the project.
     * - Throws NotFoundException if the project ID is missing, the project does not exist, or no active phase is found.
     *
     * @param string $projectId The UUID string of the project to which the task will be added.
     * 
     * @return array An array containing:
     *      - ProjectModel $project The found project instance.
     *      - PhaseModel $activePhase The ongoing phase of the project.
     * 
     * @throws NotFoundException If the project ID is missing, the project is not found, or no active phase exists.
     */
    private function addTask(string $projectId): array
    {
        if (!isset($projectId) && !trimOrNull($projectId)) {
            throw new NotFoundException("Project ID is required to add a task.");
        }

        $project = ProjectModel::findById(UUID::fromString($projectId));
        if (!$project) {
            throw new NotFoundException("Project not found.");
        }

        $activePhase = PhaseModel::findOnGoingByProjectId($project->getId());
        if (!$activePhase) {
            throw new NotFoundException("No active phase found for the project.");
        }

        return [$project, $activePhase];
    }
}
