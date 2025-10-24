<?php

namespace App\Controller;

use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Enumeration\WorkStatus;
use App\Utility\WorkerPerformanceCalculator;

class UserController implements Controller
{
    private function __construct() {}
    
    public static function index(array $args = []): void
    {
        $users = UserModel::all();

        require_once VIEW_PATH . 'users.php';
    }
}