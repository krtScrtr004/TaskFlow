<?php

class UserController implements Controller
{
    private function __construct()
    {
    }
    public static function index(array $args = []): void
    {
        $users = UserModel::all();

        require_once VIEW_PATH . 'users.php';
    }

    public static function getUserById(array $args = []): void
    {
        $userId = $args['userId'] ?? null;
        if (!$userId)
            Response::error('User ID is required.');

        if ($_GET['additionalInfo']) {
            // TODO
        }

        $users = UserModel::all();
        Response::success([$users[0]->toArray()], 'User fetched successfully.');
    }

    public static function getUserByKey(): void
    {
        $users = UserModel::all();
        Response::success($users, 'Users fetched successfully.');
    }

    public static function addUser(): void
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        Response::success([], 'User added successfully.', 201);
    }

    public static function editUser(): void
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        Response::success([], 'User edited successfully.');
    }
}