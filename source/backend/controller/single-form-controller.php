<?php

namespace App\Controller;

use App\Interface\Controller;

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
            'script' => ['password-list-validator']
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
        $instance = new self();
        $components = $instance->components;

        $page = kebabToCamelCase(explode('/', $_SERVER['REQUEST_URI'])[2]) ?? 'forgetPassword';
        $component = $components[$page];
        $scripts = $component['script'];
        $form = 'single-form' . DS . camelToKebabCase($component['form']) . '.php';

        require_once VIEW_PATH . 'single-form.php';
    }
}
