<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Interface\Controller;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use ValueError;

class UserController implements Controller
{
    private function __construct() {}

    /**
     * Handles the user listing endpoint with optional filtering and pagination.
     *
     * This method checks for an authorized session before proceeding. It supports filtering users
     * by role or worker status, and searching by a keyword. Pagination is handled via 'limit' and 'offset'
     * query parameters. The results are rendered using the 'users.php' view.
     *
     * @param array $args Optional arguments for controller logic (not used in this method).
     *
     * Query Parameters:
     *      - filter: string (optional) Role or WorkerStatus to filter users. If 'all', no filter is applied.
     *      - key: string (optional) Search keyword for user lookup.
     *      - limit: int (optional) Maximum number of users to return. Defaults to 10.
     *      - offset: int (optional) Number of users to skip for pagination. Defaults to 0.
     *
     * Exceptions:
     *      - ForbiddenException If the user session is not authorized.
     *      - NotFoundException If the requested resource is not found.
     *
     * @return void
     */
    public static function index(array $args = []): void
    {
        try {
            SessionAuth::redirectIfNotAuthorized();

            $userModel = new UserModel();
            $users = $userModel->search(
                isset($_GET['key']) ? trim($_GET['key']) : '',
                [
                    'status'    => isset($_GET['status']) ? WorkerStatus::tryFrom($_GET['status']) : null,
                    'role'      => isset($_GET['role']) ? Role::tryFrom($_GET['role']) : null,

                    'limit'     => isset($_GET['limit']) ? (int)$_GET['limit'] : 10,
                    'offset'    => isset($_GET['offset']) ? (int)$_GET['offset'] : 0
                ]
            ) ?? [];

            require_once VIEW_PATH . 'Users.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }
}
