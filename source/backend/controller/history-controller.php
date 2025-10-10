<?php

class HistoryController implements Controller{
    private function __construct() {}

    public static function index(): void {
        $projects = ProjectModel::all();

        require_once VIEW_PATH . 'history.php';
    }
}