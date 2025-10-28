<?php

namespace App\Endpoint;

use App\Core\UUID;
use App\Exception\ValidationException;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Enumeration\WorkStatus;
use App\Utility\WorkerPerformanceCalculator;

class UserEndpoint
{
    public static function getUserById(array $args = []): void
    {
        try {
            $userId = (isset($args['userId']))
                ? UUID::fromString($args['userId']) 
                : null;
            if (!$userId) {
                throw new ValidationException('User ID is required.');
            }

            $user = UserModel::findByPublicId($userId);
            if (!$user) {
                Response::error('User not found.', [], 404);
            } else {
                Response::success([$user->toArray()], 'User fetched successfully.');
            }
        } catch (ValidationException $e) {
            Response::error('Validation Error', $e->getErrors(), 422);
        }
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

    public static function create(): void
    {
        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.');

        Response::success([], 'User added successfully.', 201);
    }

    public static function edit(): void
    {
        if (count($_FILES) > 0) {
            // Handle file upload
            $profilePicture = $_FILES['profilePicture'] ?? null;
        } else {
            $data = decodeData('php://input');
            if (!$data)
                Response::error('Cannot decode data.');
        }

        Response::success([], 'User edited successfully.');
    }

    public static function delete(array $args = []): void
    {
        $userId = $args['userId'] ?? null;
        if (!$userId)
            Response::error('User ID is required.');

        // Response::error('Active Project', [
        //     'You are assigned to an active project. Complete the project or ask for termination before deleting.'
        // ]);

        Response::success([], 'User deleted successfully.');
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