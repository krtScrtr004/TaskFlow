<?php

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
            ]
        ],
    ];

    private function __construct() {}

    public static function index(): void
    {
        $session = Session::create();
        if ($session->isSet())
            $session->destroy();

        // Dynamically display appropriate page (login / signup) based on URL
        $uris = explode('/', $_SERVER['REQUEST_URI']);
        $page = kebabToCamelCase(($uris[2] !== '') ? $uris[2] : 'login');
        $component = self::$components[$page] ?? null;
        $scripts = $component['scripts'] ?? [];

        require_once VIEW_PATH . 'index.php';
    }
}
