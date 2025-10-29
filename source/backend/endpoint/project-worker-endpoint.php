<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Middleware\Csrf;
use App\Model\ProjectWorkerModel;
use App\Model\WorkerModel;
use App\Utility\WorkerPerformanceCalculator;
use Exception;

// TODO: CHECK IF THE REQUEST HAS PROJECT ID;
// IF NOT, RETURN UNASSIGNED WORKERS
class ProjectWorkerEndpoint
{

    /**
     * Retrieves a worker associated with a specific project by their IDs.
     *
     * This endpoint validates the request method and user session, then fetches a worker
     * belonging to a given project using the provided project and worker IDs. Supports pagination
     * through optional GET parameters.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier (required)
     *      - workerId: string|UUID Worker identifier (required)
     *
     * GET parameters:
     *      - limit: int (optional) Maximum number of workers to return (default: 10)
     *      - offset: int (optional) Number of workers to skip (default: 0)
     *
     * @throws ForbiddenException If the request method is not GET, session is unauthorized,
     *                           or required IDs are missing.
     * @throws ValidationException If validation fails.
     * @throws Exception For any other unexpected errors.
     *
     * @return void Outputs a JSON response with the worker(s) data or error message.
     */
    public static function getById($args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }

            $workerId = isset($args['workerId'])
                ? UUID::fromString($args['workerId'])
                : null;
            if (!$workerId) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }


            $worker = ProjectWorkerModel::findByWorkerId($workerId,  $projectId, true);
            if (!$worker) {
                Response::error( 'Worker not found for the specified project.', [], 404);
            } else {
                $performance = WorkerPerformanceCalculator::calculate($worker->getAdditionalInfo('projectHistory'));
                $worker->addAdditionalInfo('performance', $performance['overallScore']);
                Response::success([$worker], 'Worker fetched successfully.');
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
     * Retrieves workers associated with a specific project, optionally filtered by a search key.
     *
     * This method validates the request method and user session, then fetches workers for the given project.
     * If a 'key' parameter is present in the query string, it performs a search for workers matching the key.
     * Otherwise, it retrieves a paginated list of all workers for the project.
     * The method responds with a success message and the list of workers, or an error message if no workers are found or an exception occurs.
     *
     * @param array $args Associative array of arguments with the following keys:
     *      - projectId: string|UUID The unique identifier of the project (required)
     * 
     * Query Parameters:
     *      - key: string (optional) Search term to filter workers by name or other attributes
     *      - limit: int (optional) Maximum number of workers to return (default: 10)
     *      - offset: int (optional) Number of workers to skip for pagination (default: 0)
     *
     * @return void Outputs a JSON response with the list of workers or an error message
     *
     * @throws ValidationException If validation of input parameters fails
     * @throws ForbiddenException If the request method is not GET, the session is unauthorized, or the project ID is missing
     * @throws Exception For any unexpected errors
     */
    public static function getByKey(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $workers = [];
            // Check if 'key' parameter is present in the query string
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $workers = ProjectWorkerModel::search(
                    trimOrNull($_GET['key'] ?? '') ?? '',
                    $projectId
                );
            } elseif (isset($_GET['status']) && trim($_GET['status']) !== '') {
                $workers = ProjectWorkerModel::getByStatus(
                    WorkerStatus::from(trimOrNull($_GET['status'] ?? '') ?? ''),
                    $projectId,
                    [
                        'limit'     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]
                );
            } else {
                $workers = ProjectWorkerModel::findByProjectId(
                    $projectId,
                    [
                        'limit'     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]
                );
            }

            if (!$workers) {
                Response::success([], 'No workers found for the specified project.');
            } else {
                $return = [];
                foreach ($workers as $worker) {
                    $return[] = $worker;
                }
                Response::success($return, 'Workers fetched successfully.');
            }
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }


    public static function add(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }
            Csrf::protect();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!isset($projectId)) {
                throw new ForbiddenException('Project ID is required.');
            }

            $workerIds = $data['workerIds'] ?? null;
            if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
                throw new ForbiddenException('Worker IDs are required.');
            }
            
            $ids = [];
            foreach ($workerIds as $workerId) {
                $ids[] = UUID::fromString($workerId);
            }
            ProjectWorkerModel::createMultiple($projectId, $ids);
            
            Response::success([], 'Workers added successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }



    public static function edit(array $args = []): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        Response::success([], 'Worker updated successfully');
    }

    public static function terminate(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Invalid data provided');
        }

        if (!isset($data['projectId'])) {
            Response::error('Project ID is required');
        }

        if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
            Response::error('Worker IDs are required');
        }

        // TODO: Terminate worker from project logic

        Response::success([
            'message' => 'Worker terminated successfully'
        ], 'Worker terminated successfully');
    }
}