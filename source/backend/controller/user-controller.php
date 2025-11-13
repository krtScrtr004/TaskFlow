<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Endpoint\UserEndpoint;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Utility\WorkerPerformanceCalculator;
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
            if (!SessionAuth::hasAuthorizedSession()) {
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            $filter = null;
            if (isset($_GET['filter']) && trim($_GET['filter']) !== '' && strcasecmp($_GET['filter'], 'all') !== 0) {
                try {
                    $filter = Role::from($_GET['filter']);
                } catch (ValueError $e) {
                    $filter = WorkerStatus::from($_GET['filter']);
                }
            }

            $users = UserModel::search(
                isset($_GET['key']) ? trim($_GET['key']) : '',
                $filter instanceof Role ? $filter : null,
                $filter instanceof WorkerStatus ? $filter : null,
                [
                    'limit'     => isset($_GET['limit']) ? (int)$_GET['limit'] : 10,
                    'offset'    => isset($_GET['offset']) ? (int)$_GET['offset'] : 0
                ]
            ) ?? [];

            require_once VIEW_PATH . 'users.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }

    }
}