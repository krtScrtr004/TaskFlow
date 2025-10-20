<?php

namespace App\Controller;

use App\Interface\Controller;
use App\Middleware\Response;
use App\Validator\UserValidator;
use App\Model\UserModel;    
use App\Core\Me;
use App\Core\Session;

class AuthController implements Controller {
    private function __construct() {}

    public static function index(array $args = []): void {}

    public static function login(): void 
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $validator = new UserValidator();
        $validator->validateEmail($email);
        $validator->validatePassword($password);
        if ($validator->hasErrors()) {
            Response::error('Login Failed.', $validator->getErrors());
        }

        // Verify credentials
        $find = UserModel::findByEmail($email);
        if (!$find || !password_verify($password, $find['password'])) {
            Response::error('Login Failed.', [
                'Invalid email or password.'
            ]);
        }

        if (!Me::getInstance() === null) {
            Me::instantiate($find);
        }
        
        if (!Session::isSet()) {
            Session::create();
        }

        if (!Session::has('user_id')) {
            Session::set('user_id', Me::getInstance()->getId());
        }

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
