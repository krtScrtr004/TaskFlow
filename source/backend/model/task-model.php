<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\WorkerContainer;
use App\Container\TaskContainer;
use App\Exception\ValidationException;
use App\Exception\DatabaseException;
use App\Model\UserModel;
use App\Enumeration\WorkStatus;
use App\Enumeration\TaskPriority;
use App\Entity\User;
use App\Entity\Task;
use DateTime;
use InvalidArgumentException;
use PDOException;

class TaskModel extends Model
{
    /**
     * Finds all tasks belonging to a specific project along with their assigned workers.
     * 
     * This method retrieves all tasks associated with a given project ID from the database,
     * including detailed information about each task and its assigned workers. The results
     * are returned as a TaskContainer object containing Task objects, each potentially having
     * assigned workers in a WorkerContainer.
     * 
     * @param int $projectId The ID of the project to retrieve tasks for
     * @return TaskContainer|null Returns a TaskContainer with all project tasks and their workers,
     *                           or null if no tasks are found for the project
     * @throws ValidationException If the provided project ID is invalid (less than 1)
     * @throws DatabaseException If a database error occurs during the operation
     */
    public static function findAllByProjectId(int $projectId): ?TaskContainer
    {
        if ($projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        try {
            $projectTaskQuery = "
                SELECT 
                    pt.id AS taskId,
                    pt.publicId AS taskPublicId,
                    pt.title AS taskName,
                    pt.description AS taskDescription,
                    pt.startDateTime AS taskStartDateTime,
                    pt.dueDateTime AS taskCompletionDateTime,
                    pt.completionDateTime AS taskActualCompletionDateTime,
                    pt.priority AS taskPriority,
                    pt.status AS taskStatus,
                    pt.createdAt AS taskCreatedAt,
                    u.id AS workerId,
                    u.publicId AS workerPublicId,
                    u.firstName AS workerFirstName,
                    u.middleName AS workerMiddleName,
                    u.lastName AS workerLastName,
                    u.profileLink AS workerProfileLink
                FROM 
                    `projectTask` AS pt
                LEFT JOIN 
                    `projectTaskWorker` AS ptw ON pt.id = ptw.taskId
                LEFT JOIN 
                    `user` AS u ON ptw.workerId = u.id
                WHERE 
                    pt.projectId = :projectId
                ORDER BY 
                    pt.id
            ";
            $statement = self::$connection->prepare($projectTaskQuery);
            $statement->execute([':projectId' => $projectId]);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            // Group results by task
            $tasksData = [];
            foreach ($results as $row) {
                $taskId = $row['id'];

                // Initialize task data if not exists
                if (!isset($tasksData[$taskId])) {
                    $tasksData[$taskId] = [
                        'id' => $row['taskId'],
                        'publicId' => $row['taskPublicId'],
                        'title' => $row['taskName'],
                        'description' => $row['taskDescription'],
                        'startDateTime' => new DateTime($row['taskStartDateTime']),
                        'dueDateTime' => new DateTime($row['taskDueDateTime']),
                        'completionDateTime' => $row['completionDateTime']
                            ? new DateTime($row['completionDateTime'])
                            : null,
                        'priority' => TaskPriority::from($row['priority']),
                        'status' => WorkStatus::from($row['status']),
                        'createdAt' => new DateTime($row['createdAt']),
                        'workers' => []
                    ];
                }

                // Add worker if exists
                if ($row['workerId'] !== null) {
                    $tasksData[$taskId]['workers'][] = User::fromArray([
                        'id' => $row['workerId'],
                        'publicId' => $row['workerPublicId'],
                        'firstName' => $row['workerFirstName'],
                        'middleName' => $row['workerMiddleName'],
                        'lastName' => $row['workerLastName'],
                        'profileLink' => $row['workerProfileLink']
                    ])->toWorker();
                }
            }

            // Build TaskContainer
            $tasks = new TaskContainer();
            foreach ($tasksData as $taskData) {
                $workers = new WorkerContainer();
                foreach ($taskData['workers'] as $worker) {
                    $workers->add($worker);
                }

                $tasks->add(Task::fromArray([
                    'id' => $taskData['id'],
                    'publicId' => $taskData['publicId'],
                    'name' => $taskData['name'],
                    'description' => $taskData['description'],
                    'workers' => !empty($taskData['workers']) ? $workers : null,
                    'startDateTime' => $taskData['startDateTime'],
                    'dueDateTime' => $taskData['dueDateTime'],
                    'completionDateTime' => $taskData['completionDateTime'],
                    'priority' => $taskData['priority'],
                    'status' => $taskData['status'],
                    'createdAt' => $taskData['createdAt']
                ]));
            }

            return $tasks;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves all workers assigned to a specific task.
     * 
     * This method queries the database to find all users who are assigned as workers to a particular task
     * identified by the given task ID. It joins the 'user' and 'projectTaskWorker' tables to retrieve
     * worker details.
     * 
     * @param int $taskId The ID of the task to find workers for
     * @return WorkerContainer|null A container with all workers assigned to the task, or null if no workers are found
     * @throws ValidationException If the task ID is less than 1
     * @throws DatabaseException If a database error occurs during the query
     */
    public static function findWorkersByTaskId(int $taskId): ?WorkerContainer
    {
        if ($taskId < 1) {
            throw new ValidationException('Invalid Task ID');
        }

        try {
            $taskWorkerQuery = "
                SELECT 
                    u.id,
                    u.publicId,
                    u.firstName,
                    u.middleName,
                    u.lastName,
                    u.profileLink
                FROM 
                    `user` AS u
                INNER JOIN 
                    `projectTaskWorker` AS tw 
                ON 
                    u.id = tw.workerId
                WHERE 
                    tw.taskId = :taskId";
            $statement = self::$connection->prepare($taskWorkerQuery);
            $statement->execute([':taskId' => $taskId]);
            $result = $statement->fetchAll();

            if (!empty($result)) {
                $workers = new WorkerContainer();
                foreach ($result as $item) {
                    $workers->add(User::fromArray($item)->toWorker());
                }
                return $workers;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }










    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public static function create(mixed $data): void
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of TaskModel');
        }
    }

    public static function all(): TaskContainer
    {
        $workers = UserModel::all();
        $workerContainer = new WorkerContainer();
        foreach ($workers as $worker) {
            $workerContainer->add($worker->toWorker());
        }

        $tasks = new TaskContainer();
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 1',
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            $workerContainer,
            new DateTime('2023-01-02 09:00:00'),
            new DateTime('2023-01-04 17:00:00'),
            new DateTime('2023-01-03 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::COMPLETED,
            new DateTime('2023-01-05 10:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 2',
            'This is the second task.',
            $workerContainer,
            new DateTime('2023-02-01 09:00:00'),
            new DateTime('2023-02-05 17:00:00'),
            new DateTime('2023-02-05 14:00:00'),
            TaskPriority::MEDIUM,
            WorkStatus::COMPLETED,
            new DateTime('2023-01-15 11:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 3',
            'Lorem ipsum dolor sit amet. consectetur adipiscing elit. Lorem ipsum dolor sit amet. consectetur adipiscing elit. Lorem ipsum dolor sit amet. consectetur adipiscing elit.',
            $workerContainer,
            new DateTime('2023-03-10 09:00:00'),
            new DateTime('2023-03-15 17:00:00'),
            new DateTime('2023-03-11 16:00:00'),
            TaskPriority::LOW,
            WorkStatus::COMPLETED,
            new DateTime('2023-03-01 12:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 4',
            'This is the fourth task.',
            $workerContainer,
            new DateTime('2023-04-01 09:00:00'),
            new DateTime('2023-04-10 17:00:00'),
            new DateTime('2023-04-05 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::COMPLETED,
            new DateTime('2023-03-20 13:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 5',
            'This is the fifth task.',
            $workerContainer,
            new DateTime('2023-05-01 09:00:00'),
            new DateTime('2023-05-07 17:00:00'),
            new DateTime('2023-05-07 16:00:00'),
            TaskPriority::MEDIUM,
            WorkStatus::COMPLETED,
            new DateTime('2023-04-25 14:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 6',
            'This is the sixth task.',
            $workerContainer,
            new DateTime('2023-06-01 09:00:00'),
            new DateTime('2023-06-05 17:00:00'),
            new DateTime('2023-06-04 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::COMPLETED,
            new DateTime('2023-05-20 15:00:00'),
        ));
        return $tasks;
    }

    public static function find($id): ?self
    {
        return null;
    }

}