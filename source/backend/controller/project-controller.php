<?php

class ProjectController implements Controller{
    private function __construct() {}

    public static function index(): void
    {
        require_once VIEW_PATH . 'project.php';
    }
}