<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Entity\Task;
use App\Enumeration\WorkStatus;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\TaskModel;
use App\Validator\WorkValidator;
use DateTime;
use Exception;
use InvalidArgumentException;

class TaskEndpoint
{
    public static function getById(array $args = []): void
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

            if (isset($args['projectId']) && ProjectModel::findById($projectId) === null) {
                throw new NotFoundException('Project not found.');
            }

            $taskId = isset($args['taskId'])
                ? UUID::fromString($args['taskId'])
                : null;
            if (!$taskId) {
                throw new ForbiddenException('Task ID is required.');
            }

            $task = TaskModel::findById($taskId, $projectId);
            if ($task === null) {
                throw new NotFoundException('Task not found.');
            }

            Response::success([$task], 'Task fetched successfully.');
        } catch (ValidationException $e) {
            Response::error('Validation Failed.', $e->getErrors(), 422);
        } catch (ForbiddenException $e) {
            Response::error('Forbidden.', [], 403);
        } catch (NotFoundException $e) {
            Response::error('Resource Not Found.', [$e->getMessage()], 404);
        } catch (Exception $e) {
            Response::error('Unexpected Error.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }

    public static function getTaskByKey(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid request method. GET request required.');
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

            $tasks = [];
            // Check if 'key' parameter is present in the query string
            if (isset($_GET['key']) && trim($_GET['key']) !== '') {
                $tasks = TaskModel::search(
                    trimOrNull($_GET['key'] ?? '') ?? '',
                    $projectId
                );
            } elseif (isset($_GET['status']) && trim($_GET['status']) !== '') {
                $tasks = TaskModel::findByStatus(
                    WorkStatus::from(trimOrNull($_GET['status'] ?? '') ?? ''),
                    $projectId,
                    [
                        'limit'     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]
                );
            } else {
                if ($projectId) {
                    $tasks = TaskModel::findAllByProjectId($projectId, [
                        'limit'     => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                        'offset'    => isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
                    ]);
                } else {
                    $tasks = TaskModel::all(
                        $_GET['offset'] ? (int) $_GET['offset'] : 0,
                        $_GET['limit'] ? (int) $_GET['limit'] : 10
                    );
                }
            }

            if (!$tasks) {
                Response::success([], 'No tasks found for the specified project.');
            } else {
                $return = [];
                foreach ($tasks as $worker) {
                    $return[] = $worker;
                }
                Response::success($return, 'Tasks fetched successfully.');
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

            $project = ProjectModel::findById($projectId);
            if (!$project) {
                throw new NotFoundException('Project not found.');
            }

            $validator = new WorkValidator();
            $validator->validateDateBounds(
                new DateTime($data['startDateTime']),
                new DateTime($data['completionDateTime']),
                $project->getStartDateTime(),
                $project->getCompletionDateTime()
            );
            if ($validator->hasErrors()) {
                throw new ValidationException('Task Validation Failed.', $validator->getErrors());
            }

            $task = Task::createPartial([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'startDateTime' => $data['startDateTime'],
                'completionDateTime' => $data['completionDateTime'],
                'priority' => $data['priority'],
            ]);

            $workerIds = $data['workerIds'] ?? null;
            if (!isset($data['workerIds']) || !is_array($data['workerIds']) || count($data['workerIds']) < 1) {
                throw new ForbiddenException('Worker IDs are required.');
            }
            
            foreach ($workerIds as $workerId) {
                $task->addWorker(Worker::createPartial([
                    'publicId' => UUID::fromString($workerId)
                ]));
            }
            $task->addAdditionalInfo('projectId', $project->getId());

            TaskModel::create($task);
            
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
        $projectId = $args['projectId'] ?? null; // Temporary placeholder
        if ($projectId === null)
            throw new InvalidArgumentException('Project ID is required to edit a task.');
        $taskId = $args['taskId'] ?? null; // Temporary placeholder
        if ($taskId === null)
            throw new InvalidArgumentException('Task ID is required to edit a task.');

        $data = decodeData('php://input');
        if (!$data)
            Response::error('Cannot decode data.', []);

        Response::success([], 'Task updated successfully.');
    }
}