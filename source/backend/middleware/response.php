<?php

class Response
{
    private function __construct()
    {
    }

    public static function success($data, $message, $statusCode = 200): void
    {
        self::sendResponse($statusCode, [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function error($message, $errors = [], $statusCode = 400): void
    {
        self::sendResponse($statusCode, [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ]);
    }

    private static function sendResponse($statusCode, $body = [])
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode($body);
        exit();
    }
}