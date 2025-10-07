<?php

class SingleFormController implements Controller
{
    private array $components = [
        'forgetPassword' => [
            'title' => 'Reset Your Password',
            'description' => 'Enter your email address below and we will send you a link to reset your password.',
            'form' => 'forgetPassword',
            'script' => ['single-form/forget-password/send-link']
        ],
        'changePassword' => [
            'title' => 'Set Your Password',
            'description' => 'Create a new password.',
            'form' => 'changePassword',
            'script' => ['password-list-validator']
        ],
        'createProject' => [
            'title' => 'Create New Project',
            'description' => 'Fill in the details below to create a new project.',
            'form' => 'createProject',
            'script' => [
                'single-form/edit-project/phase/open',
                'single-form/edit-project/phase/create/add',
                'single-form/edit-project/phase/create/cancel',
            ]
        ],
        'editProject' => [
            'title' => 'Edit Project Details',
            'description' => 'Modify the details of your project below.',
            'form' => 'editProject',
            'script' => [
                'single-form/edit-project/phase/open',
                'single-form/edit-project/phase/edit/cancel',
                'single-form/edit-project/phase/edit/add',
                'single-form/edit-project/phase/edit/submit',
            ],
        ],
        'addTask' => [
            'title' => '',
            'description' => 'Fill in the details below to add a new task.',
            'form' => 'addTask',
            'script' => [
                'add-worker-modal/add-to-task',
                'add-worker-modal/close',
                'add-worker-modal/open',
                'add-worker-modal/search',
                'single-form/add-task/submit',
            ]
        ]
    ];

    private function __construct()
    {
    }

    public static function index(array $args = []): void
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
