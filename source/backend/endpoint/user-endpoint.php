<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Utility\WorkerPerformanceCalculator;
use Exception;

class UserEndpoint
{
    /**
     * Retrieves a user by their unique identifier.
     *
     * This method attempts to fetch a user from the database using the provided user ID.
     * It performs validation on the input, handles exceptions, and returns an appropriate
     * HTTP response based on the outcome:
     * - Validates that a user ID is provided and is a valid UUID.
     * - Returns a 404 error if the user is not found.
     * - Returns a 422 error if validation fails.
     * - Returns a 403 error if access is forbidden.
     * - Returns a 500 error for unexpected exceptions.
     * - On success, returns the user data with a success message.
     *
     * @param array $args Associative array of arguments with the following key:
     *      - userId: string The UUID string of the user to retrieve.
     *
     * @return void Outputs a JSON response with user data or error message.
     */
    public static function getUserById(array $args = []): void
    {
        try {
            $userId = (isset($args['userId']))
                ? UUID::fromString($args['userId']) 
                : null;
            if (!$userId) {
                throw new ValidationException('User ID is required.');
            }

            $user = UserModel::finById($userId); 
            if (!$user) {
                Response::error('User not found.', [], 404);
            } else {
                Response::success([$user], 'User fetched successfully.');
            }
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    /**
     * Retrieves a list of users filtered by a search key or returns all users.
     *
     * This endpoint supports searching for users by a provided key or fetching all users if no key is specified.
     * It enforces GET request method and requires an authorized user session.
     * Supports pagination via 'limit' and 'offset' query parameters.
     *
     * Query Parameters:
     *      - key: string (optional) Search term to filter users
     *      - limit: int (optional) Maximum number of users to return (default: 10)
     *      - offset: int (optional) Number of users to skip for pagination (default: 0)
     *
     * Responses:
     *      - 200: Success, returns an array of user data or an empty array if no users found
     *      - 403: Forbidden, if the request method is not GET or the session is unauthorized
     *      - 422: Validation failed, returns validation errors
     *      - 500: Unexpected error, returns a generic error message
     *
     * @throws ValidationException If validation of input parameters fails
     * @throws ForbiddenException If the request method is not GET or session is unauthorized
     * @throws Exception For any other unexpected errors
     *
     * @return void Outputs a JSON response with user data or error information
     */
    public static function getUsersByKey(): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }

            $users = [];
            // Check if 'key' parameter is present in the query string
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $users = UserModel::search(
                    trim($_GET['key']),
                    [
                        'limit'     => isset($_GET['limit']) ? (int)$_GET['limit'] : 10,
                        'offset'    => isset($_GET['offset']) ? (int)$_GET['offset'] : 0
                    ]
                );
            } else {
                $users = UserModel::all(
                    isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
                    isset($_GET['limit']) ? (int)$_GET['limit'] : 10
                );
            }

            if (!$users) {
                Response::success([], 'No users found.');
            } else {
                $return = [];
                foreach ($users as $user) {
                    $return[] = $user;
                }
                Response::success($return, 'Users fetched successfully.');
            }
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
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