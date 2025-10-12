<?php

class AuthController implements Controller {
    private function __construct() {}

    public static function index(array $args = []): void {}

    public static function login(): void 
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        Response::error('Login failed.', [
            'Invalid email or password.'
        ], 401);

        Response::success([
            'projectId' => 'P12345'
        ], 'Login successful.');
    }

    public static function register(): void 
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // Response::error('Email already in use.', [], 409);

        Response::success([], 'Registration successful. Please verify your email before logging in.', 201);
    }
}
