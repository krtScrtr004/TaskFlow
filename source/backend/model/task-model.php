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
     * Finds and retrieves task data from the database based on provided criteria.
     *
     * This method fetches task data including associated workers from the database,
     * organizing the results into task objects within a TaskContainer.
     * 
     * The method performs the following operations:
     * - Executes a JOIN query between project tasks and workers
     * - Processes pagination and sorting options if provided
     * - Groups results by task and associates workers with their respective tasks
     * - Converts database rows to strongly typed Task and Worker objects
     * - Builds proper object relationships between tasks and workers
     *
     * @param string $whereClause SQL WHERE clause condition (without the 'WHERE' keyword)
     * @param array $params Prepared statement parameters for the WHERE clause
     * @param array $options Query options with the following possible keys:
     *      - offset: int The number of rows to skip
     *      - limit: int Maximum number of rows to return
     *      - orderBy: string SQL ORDER BY clause (without the 'ORDER BY' keywords)
     * 
     * @return TaskContainer|null TaskContainer with Task objects if results found, null if no results
     * @throws DatabaseException If a database error occurs during execution
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?TaskContainer
    {
        $instance = new self();
        try {
            $projectTaskQueryString = "
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
            ";
            $projectTaskQuery = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($projectTaskQueryString, $whereClause),
                $options);

            $statement = $instance->connection->prepare($projectTaskQuery);
            $statement->execute($params);
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
     * Finds all tasks associated with a specific project.
     *
     * This method retrieves all tasks that belong to a given project ID from the database.
     * It validates the project ID before performing the database query.
     *
     * @param int $projectId The ID of the project to find tasks for
     * @return TaskContainer|null A container with all tasks for the specified project, or null if no tasks found
     * @throws ValidationException If the project ID is less than 1
     * @throws DatabaseException If there's an error during the database operation
     */
    public static function findAllByProjectId(int $projectId): ?TaskContainer
    {
        if ($projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        try {
            $tasks = self::find('projectId = :projectId', [':projectId' => $projectId]);
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

        $instance = new self();
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
            $statement = $instance->connection->prepare($taskWorkerQuery);
            $statement->execute([':taskId' => $taskId]);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $item) {
                $workers->add(User::fromArray($item)->toWorker());
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves all tasks with pagination.
     *
     * This method fetches tasks from the database with optional pagination parameters:
     * - Uses offset to skip a certain number of records
     * - Uses limit to restrict the number of records returned
     * - Validates input parameters before executing the query
     * - Handles database exceptions by wrapping them in a DatabaseException
     *
     * @param int $offset Number of records to skip (must be non-negative)
     * @param int $limit Maximum number of records to return (must be positive)
     * 
     * @throws InvalidArgumentException If offset is negative or limit is less than 1
     * @throws DatabaseException If a database error occurs during the query
     * 
     * @return TaskContainer|null An array of task records or null if no records found
     */
    public static function all(int $offset = 0, int $limit = 10): ?TaskContainer
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Invalid offset value.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Invalid limit value.');
        }

        try {
            $tasks = self::find('', [], ['offset' => $offset, 'limit' => $limit]);
            return $tasks;
        } catch (PDOException $th) {
            throw new DatabaseException($th->getMessage());
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

    public static function create(mixed $data): mixed
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of TaskModel');
        }
        return null;
    }
}