<?php

class AuthController implements Controller {
    private function __construct() {}

    public static function index(array $args = []): void {}

    public static function login(): void 
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // Response::error('Invalid email or password.', [], 401);

        Response::success([
            'projectId' => 'P12345'
        ], 'Login successful.');
    }
}
