<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Interface\Controller;
use App\Core\Session;
use App\Middleware\Csrf;

class IndexController implements Controller
{
    private static array $components = [
        'login' => [
            'title' => 'Log In Your Account', 
            'form'  => 'login',
            'scripts' => [
                'login/submit'
            ]
        ],
        'register' => [
            'title' => 'Register An Account', 
            'form'  => 'register',
            'scripts' => [
                'check-date-validity',
                'register/select-role',
                'register/submit'
            ]
        ],
    ];

    private function __construct() {}

    /**
     * Handles the logic for displaying the index page (login/signup) and session management.
     *
     * This method performs the following actions:
     * - Redirects authenticated users to the homepage.
     * - Ensures a session exists for unauthenticated users.
     * - Generates and persists a CSRF token if not already set.
     * - Dynamically determines which page component (login/signup) to display based on the URL.
     * - Loads the appropriate scripts for the selected component.
     * - Renders the main index view.
     *
     * No parameters are required.
     *
     * @return void
     */
    public static function index(): void
    {
        // If user is already logged in, redirect to homepage instead of showing login page
        if (SessionAuth::hasAuthorizedSession()) {
            $projectId = Session::get('activeProjectId') ?? '';
            header('Location: ' . REDIRECT_PATH . 'home');
            exit();
        }

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

        // Dynamically display appropriate page (login / signup) based on URL
        $uris = explode('/', $_SERVER['REQUEST_URI']);
        $page = kebabToCamelCase(($uris[2] !== '') ? $uris[2] : 'login');
        $component = self::$components[$page] ?? null;
        $scripts = $component['scripts'] ?? [];

        require_once VIEW_PATH . 'index.php';
    }

    /**
     * Handles the email confirmation view for unauthenticated users.
     *
     * This method ensures that a session exists and a CSRF token is set for security purposes.
     * If the session is not initialized, it creates one. If the CSRF token is missing, it generates
     * a new token, forces the session to be written and closed, then restores the session for the
     * remainder of the request. Finally, it loads the email confirmation sub-view.
     *
     * No parameters are required.
     *
     * @return void
     */
    public static function confirmEmail(): void
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

        require_once SUB_VIEW_PATH . 'confirm-email.php';
    }
}
