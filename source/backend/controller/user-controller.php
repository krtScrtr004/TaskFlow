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
        Response::success([self::createResponseArrayData($users[0]->toWorker())], 'User fetched successfully.');
    }

    public static function getUserByKey(): void
    {
        $users = UserModel::all();
        $return = [];
        foreach ($users as $user) {
            $return[] = self::createResponseArrayData($user->toWorker());
        }
        Response::success($return, 'Users fetched successfully.');
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

    private static function createResponseArrayData(Worker $worker): array
    {
        $worker->setRole(Role::WORKER);
        $projects = ProjectModel::all();
        $workerPerformanceProject = WorkerPerformanceCalculator::calculate($projects);
        return [
            ...$worker->toArray(),
            'totalProjects' => count($projects),
            'completedProjects' => $projects->getCountByStatus(WorkStatus::COMPLETED),
            'performance' => $workerPerformanceProject['overallScore'],
        ];
    }
}