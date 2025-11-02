<?php

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Model\TaskWorkerModel;
use App\Model\UserModel;
use App\Utility\WorkerPerformanceCalculator;

class TaskWorkerEndpoint
{
    // private static function createResponseArrayData(Worker $worker): array
    // {
    //     $worker->setRole(Role::WORKER);
    //     $projects = ProjectModel::all();
    //     $workerPerformanceProject = WorkerPerformanceCalculator::calculate($projects);
    //     return [
    //         ...$worker->toArray(),
    //         'totalProjects' => $workerPerformanceProject['totalProjects'],
    //         'completedProjects' => $projects->getCountByStatus(WorkStatus::COMPLETED),
    //         'performance' => $workerPerformanceProject['overallScore'],
    //     ];
    // }

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

            $taskId =  isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;

            $worker = TaskWorkerModel::findById($workerId,  $projectId, $taskId);
            if (!$worker) {
                throw new NotFoundException('Worker not found.');
            } 

            // $performance = WorkerPerformanceCalculator::calculate($worker->getAdditionalInfo('projectHistory'));
            // $worker->addAdditionalInfo('performance', $performance['overallScore']);
            Response::success([$worker], 'Worker fetched successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.',$e->getErrors(),422);
        } catch (NotFoundException $e) {
            Response::error('Resource Not Found.', [$e->getMessage()], 404);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    // Used to fetch multiple workers by IDs or name filter (eg. /get-worker-info?ids=1,2,3 or /get-worker-info?name=John)
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

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;

            $workers = [];
            if (isset($_GET['ids']) && trim($_GET['ids']) !== '') {
                $ids = explode(',', trimOrNull($_GET['ids'] ?? ''));
                $uuids = [];
                foreach ($ids as $id) {
                    $uuids[] = UUID::fromString($id);
                }
                $workers = TaskWorkerModel::findMultipleById($uuids, $taskId, $projectId);
            } else {
                $key = null;
                if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                    $key = trimOrNull($_GET['key'] ?? '');
                }

                $status = null;
                if (isset($_GET['status']) && trim($_GET['status']) !== '') {
                    $status = WorkerStatus::from(trimOrNull($_GET['status'] ?? ''));
                }

                $excludeTaskTerminated = false;
                if (isset($_GET['excludeTaskTerminated']) && trim($_GET['excludeTaskTerminated']) !== '') {
                    $excludeTaskTerminated = (bool) $_GET['excludeTaskTerminated'];
                    if ($excludeTaskTerminated && !isset($projectId)) {
                        throw new ForbiddenException('Project ID is required when excluding terminated task workers.');
                    }
                }

                $workers = TaskWorkerModel::search(
                    $key,
                    $taskId,
                    $projectId,
                    $status,
                    [
                        'excludeTaskTerminated'  => $excludeTaskTerminated,
                        'limit'                     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'                    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]
                );
            }
            if (!$workers) {
                Response::success([], 'No workers found for the specified task.');
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
    //     $data = decodeData('php://input');
    //     if (!$data) {
    //         Response::error('Invalid data provided');
    //     }

    //     if (!isset($args['projectId'])) {
    //         Response::error('Project ID is required');
    //     }

    //     if (!isset($args['taskId'])) {
    //         Response::error('Task ID is required');
    //     }

    //     $workerIds = $data['workerIds'] ?? null;
    //     if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
    //         Response::error('Worker IDs are required');
    //     }

    //     $returnData = $data['returnData'] ?? false;

    //     // TODO: Add worker to project logic

    //     $returnDataArray = [];
    //     if ($returnData) {
    //         foreach ($workerIds as $workerId) {
    //             // TODO: Fetch User
    //             $user = UserModel::all()[0];

    //             $userPerformance = WorkerPerformanceCalculator::calculate(ProjectModel::all());
    //             $returnDataArray[] = [
    //                 'id' => $user->getPublicId(),
    //                 'name' => $user->getFirstName() . ' ' . $user->getLastName(),
    //                 'profilePicture' => $user->getProfileLink(),
    //                 'bio' => $user->getBio(),
    //                 'email' => $user->getEmail(),
    //                 'contactNumber' => $user->getContactNumber(),
    //                 'role' => $user->getRole()->value,
    //                 'jobTitles' => $user->getJobTitles()->toArray(),
    //                 'totalTasks' => count(ProjectModel::all()),
    //                 'completedTasks' => ProjectModel::all()->getCountByStatus(WorkStatus::COMPLETED),
    //                 'performance' => $userPerformance['overallScore'],
    //             ];
    //         }

    //     }

    //     Response::success($returnDataArray, 'Worker added successfully');
    }

}