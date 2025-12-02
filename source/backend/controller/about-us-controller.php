<?php

namespace App\Controller;

use App\Interface\Controller;

class AboutUsController implements Controller 
{
    public static function index(): void 
    {
        require_once VIEW_PATH . 'about-us.php';
    }
}