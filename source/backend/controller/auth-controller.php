<?php

class AuthController implements Controller {
    private function __construct() {}

    public static function index(array $args = []): void {}

    public static function login(): void 
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        // Check email
        $email = $data['email'] ?? null;
        if (!$email)
            Response::error('Login Failed.', [
                'Email is required.'
            ]);

        // Check password
        $password = $data['password'] ?? null;
        if (!$password)
            Response::error('Login Failed.', [
                'Password is required.'
            ]);

        // Verify credentials
        $find = UserModel::findByEmail($email);
        if (!$find || !password_verify($password, $find['password']))
            Response::error('Login Failed.', [
                'Invalid email or password.'
            ]);

        if (!Me::getInstance() === null) 
            Me::instantiate($find);
        
        if (!Session::isSet()) 
            Session::create();

        if (!Session::has('user_id')) 
            Session::set('user_id', Me::getInstance()->getId());

        Response::success([
            'projectId' => Me::getInstance()->getPublicId()
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
