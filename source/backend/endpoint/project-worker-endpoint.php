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
use App\Exception\NotFoundException;
use App\Middleware\Csrf;
use App\Model\ProjectWorkerModel;
use App\Model\WorkerModel;
use App\Utility\ResponseExceptionHandler;
use App\Utility\WorkerPerformanceCalculator;
use Exception;
use Throwable;

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
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
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

            $project = ProjectModel::findById($projectId);
            if (!isset($projectId) && !$project) {
                throw new NotFoundException('Project not found.');
            }


            $worker = ProjectWorkerModel::findById($workerId,  $project->getId() ?? null, true);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            } 

            $performance = WorkerPerformanceCalculator::calculate($worker->getAdditionalInfo('projectHistory'));
            $worker->addAdditionalInfo('performance', $performance['overallScore']);
            Response::success([$worker], 'Worker fetched successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Worker Fetch Failed.', $e);
        }
    }


    /**
     * Retrieves project workers based on provided criteria.
     *
     * This method handles GET requests to fetch workers associated with a specific project.
     * It supports searching by worker IDs, key, status, and exclusion of terminated workers.
     * The method enforces session authorization and request method validation.
     * 
     * Request parameters (via $args and $_GET):
     *      - projectId: string|UUID Project identifier (required for most queries)
     *      - ids: string Comma-separated list of worker IDs (optional)
     *      - key: string Search keyword for worker filtering (optional)
     *      - status: string|WorkerStatus Worker status filter (optional)
     *      - excludeProjectTerminated: bool Exclude terminated workers (optional, requires projectId)
     *      - limit: int Maximum number of workers to return (optional, default: 10)
     *      - offset: int Number of workers to skip for pagination (optional, default: 0)
     * 
     * Response:
     *      - Success: Array of worker data and a success message
     *      - Error: Appropriate error message and HTTP status code
     * 
     * @param array $args Associative array containing request arguments, including:
     *      - projectId: string|UUID Project identifier
     * 
     * @return void Outputs JSON response with worker data or error information
     * 
     * @throws ForbiddenException If request method is not GET, session is unauthorized, or required parameters are missing
     * @throws NotFoundException If the specified project is not found
     * @throws ValidationException If input validation fails
     * @throws Exception For unexpected errors
     */
    public static function getByKey(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (!isset($projectId) && !$project) {
                throw new NotFoundException('Project not found.');
            }

            $workers = [];
            if (isset($_GET['ids']) && trim($_GET['ids']) !== '') {
                $ids = explode(',', trimOrNull($_GET['ids'] ?? ''));
                $uuids = [];
                foreach ($ids as $id) {
                    $uuids[] = UUID::fromString($id);
                }
                $workers = ProjectWorkerModel::findMultipleById($uuids, $project->getId() ?? null, false);
            } else {
                $key = null;
                if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                    $key = trimOrNull($_GET['key'] ?? '');
                }

                $status = null;
                if (isset($_GET['status']) && trim($_GET['status']) !== '') {
                    $status = WorkerStatus::from(trimOrNull($_GET['status'] ?? ''));
                }

                $excludeProjectTerminated = false;
                if (isset($_GET['excludeProjectTerminated']) && trim($_GET['excludeProjectTerminated']) !== '') {
                    $excludeProjectTerminated = (bool) $_GET['excludeProjectTerminated'];
                    if ($excludeProjectTerminated && !isset($projectId)) {
                        throw new ForbiddenException('Project ID is required to exclude terminated workers.');
                    }
                }

                $workers = ProjectWorkerModel::search(
                    $key,
                    $project->getId() ?? $projectId,
                    $status,
                    [
                        'excludeProjectTerminated'  => $excludeProjectTerminated,
                        'limit'                     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'                    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
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
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Workers Fetch Failed.', $e);
        }
    }

    /**
     * Adds multiple workers to a project.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized.
     * - Validates the CSRF token.
     * - Decodes JSON input data from the request body.
     * - Validates the presence and format of projectId and workerIds.
     * - Converts projectId and each workerId to UUID objects.
     * - Calls the ProjectWorkerModel to associate the workers with the project.
     * - Returns a success response if all operations succeed.
     * - Handles and returns appropriate error responses for validation, authorization, and unexpected errors.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier (required)
     *
     * Input JSON body should contain:
     *      - workerIds: array List of worker IDs (string or UUID) to add to the project (required)
     *
     * @throws ValidationException If input data is invalid or cannot be decoded.
     * @throws ForbiddenException If the session is unauthorized or required parameters are missing.
     * @throws Exception For any other unexpected errors.
     *
     * @return void
     */
    public static function add(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
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

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $workerIds = $data['workerIds'] ?? null;
            if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
                throw new ForbiddenException('Worker IDs are required.');
            }
            
            $ids = [];
            foreach ($workerIds as $workerId) {
                $ids[] = UUID::fromString($workerId);
            }
            ProjectWorkerModel::createMultiple($project->getId(), $ids);
            
            Response::success([], 'Workers added successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Add Workers Failed.', $e);
        }
    }

    /**
     * Edits the status of a worker assigned to a project.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized.
     * - Protects against CSRF attacks.
     * - Validates and retrieves the project ID and worker ID from the input arguments.
     * - Finds the corresponding project and worker records.
     * - Decodes the input data from the request body.
     * - Updates the worker's status for the specified project.
     * - Returns a success response if the update is successful.
     * - Handles validation, authorization, and unexpected errors with appropriate responses.
     *
     * @param array $args Associative array containing:
     *      - projectId: string|UUID Project identifier
     *      - workerId: string|UUID Worker identifier
     * 
     * Input Data (decoded from request body):
     *      - status: string|WorkerStatus New status for the worker
     *
     * @throws ForbiddenException If the session is unauthorized or required IDs are missing.
     * @throws NotFoundException If the project or worker is not found.
     * @throws ValidationException If the input data cannot be decoded or is invalid.
     * @throws Exception For any other unexpected errors.
     *
     * @return void
     */
    public static function edit(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!isset($projectId)) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $workerId = isset($args['workerId']) 
                ? UUID::fromString($args['workerId']) 
                : null;
            if (!isset($workerId)) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = ProjectWorkerModel::findById($workerId, $project->getId(), true);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            ProjectWorkerModel::save([
                'projectId'     => $project->getId(),
                'workerId'      => $worker->getId(),
                'status'        => isset($data['status']) ? WorkerStatus::from($data['status']) : null,
            ]);

            Response::success([], 'Worker status updated successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Edit Worker Status Failed.', $e);
        }
    }

    /**
     * Removes a worker from a project.
     *
     * This endpoint handler performs the following steps:
     * - Verifies an authorized session is present.
     * - Validates the CSRF token.
     * - Converts provided projectId and workerId values to UUID objects.
     * - Loads and validates the existence of the specified project.
     * - Loads and validates the existence of the specified project worker (scoped to the project).
     * - Deletes the worker assignment from the given project.
     * - Sends a success response on successful removal; any exceptions are forwarded to the response exception handler.
     *
     * @param array $args Associative array containing request parameters:
     *      - projectId: string|UUID Project identifier (required)
     *      - workerId: string|UUID Worker identifier (required)
     *
     * @return void
     *
     * @throws ForbiddenException If the user is not authorized or if required IDs are missing/invalid.
     * @throws CsrfException If CSRF protection fails.
     * @throws NotFoundException If the referenced project or worker does not exist.
     * @throws \InvalidArgumentException If UUID parsing fails for provided IDs.
     * @throws Throwable For any other unexpected errors which will be handled by the ResponseExceptionHandler.
     */
    public static function delete(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (!isset($projectId)) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $workerId = isset($args['workerId']) 
                ? UUID::fromString($args['workerId']) 
                : null;
            if (!isset($workerId)) {
                throw new ForbiddenException('Worker ID is required.');
            }

            $worker = ProjectWorkerModel::findById($workerId, $project->getId(), true);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            }

            ProjectWorkerModel::delete([
                'projectId'     => $project->getId(),
                'workerId'      => $worker->getId(),
            ]);

            Response::success([], 'Worker removed from project successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Remove Worker Failed.', $e);
        }
    }
}