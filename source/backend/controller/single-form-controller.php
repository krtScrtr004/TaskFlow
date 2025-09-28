<?php

class SingleFormController implements Controller
{
    private array $components = [
        'forgetPassword' => [
            'title' => 'Reset Your Password',
            'description' => 'Enter your email address below and we will send you a link to reset your password.',
            'form'  => 'forgetPassword',
            'script' => null
        ]
    ];

    private function __construct() {}

    public static function index(): void
    {
        $instance = new self();
        $components = $instance->components;

        $page = kebabToCamelCase(explode('/', $_SERVER['REQUEST_URI'])[2]) ?? 'forgetPassword';
        $component = $components[$page];
        $scripts = $component['script'];
        $form = 'single-form' . DS . camelToKebabCase($component['form']) . '.php';

        require_once VIEW_PATH . 'single-form.php';
    }
}
