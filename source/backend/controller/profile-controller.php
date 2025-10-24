<?php

namespace App\Controller;

use App\Interface\Controller;

class ProfileController implements Controller {
    public static function index(): void 
    {
        require_once VIEW_PATH . 'profile.php';
    }
}