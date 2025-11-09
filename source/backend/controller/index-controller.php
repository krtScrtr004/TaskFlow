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

    public static function index(): void
    {
        // If user is already logged in, redirect to homepage instead of showing login page
        if (SessionAuth::hasAuthorizedSession()) {
            $projectId = Session::get('activeProjectId') ?? '';
            header('Location: ' . REDIRECT_PATH . 'home' . DS . $projectId);
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
}
