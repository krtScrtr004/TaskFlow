<?php

namespace App\Controller;

use App\Interface\Controller;

class ErrorController implements Controller
{
    public static function index(array $component = []): void
    {
        require_once VIEW_PATH . 'http-error.php';
    }

    public static function notFound(): void
    {
        self::index([
            'title' => '404 Not Found',
            'status' => '404'
        ]);
        http_response_code(404);
    }

    public static function forbidden(): void
    {
        self::index([
            'title' => '403 Forbidden',
            'status' => '403'
        ]); 
        http_response_code(403);
    }
}