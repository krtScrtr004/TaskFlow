<?php

namespace App\Controller;

use App\Core\Me;
use App\Core\Session;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;
use App\Middleware\Csrf;
use App\Model\TemporaryLinkModel;
use DateTime;

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

        try {
            // Check token validity for change password page
            if ($page === 'changePassword') {
                $instance->changePassword();
            }

            $scripts = $component['script'];
            $form = 'single-form' . DS . camelToKebabCase($component['form']) . '.php';

            require_once VIEW_PATH . 'single-form.php';
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        }
    }

    /**
     * Handles the password change process using a reset token.
     *
     * This method performs the following steps:
     * - Retrieves the user's email from session or current user instance.
     * - Validates the presence and format of the email.
     * - Retrieves and validates the password reset token from the GET request.
     * - Verifies the token's validity using TemporaryLinkModel.
     * - Checks if the password reset link has expired (valid for 5 minutes).
     * - Throws appropriate exceptions for missing email, missing/invalid token, or expired link.
     *
     * @throws ForbiddenException If the email is not found, token is not provided, or the link has expired.
     * @throws NotFoundException If the provided token is invalid.
     */
    private function changePassword(): void
    {
        $email = Session::get('temporaryResetEmail') ?? Me::getInstance()?->getEmail() ?? null;
        if (!$email || !trimOrNull($email)) {
            throw new ForbiddenException('Email not found for password reset.');
        }

        $token = $_GET['token'];
        if (!$token || !isset($token) || !trimOrNull($token)) {
            throw new ForbiddenException('Token not provided.');
        }

        // Verify token validity
        $isValid = TemporaryLinkModel::search($email, $token);
        if (!$isValid) {
            throw new NotFoundException('Invalid token provided.');
        }

        // Check if the link has expired (valid for 5 minutes)
        $createdAt = new DateTime($isValid['updatedAt'] ?? $isValid['createdAt']);
        if ((new DateTime())->getTimestamp() - $createdAt->getTimestamp() > 300) { // Expires in 5 minutes
            TemporaryLinkModel::delete($email);
            throw new ForbiddenException('The password reset link has expired. Please request a new one.');
        }
    }
}
