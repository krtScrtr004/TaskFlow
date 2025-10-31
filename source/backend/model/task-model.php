<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\JobTitleContainer;
use App\Container\WorkerContainer;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Exception\ValidationException;
use App\Exception\DatabaseException;
use App\Model\UserModel;
use App\Enumeration\WorkStatus;
use App\Enumeration\TaskPriority;
use App\Entity\User;
use App\Entity\Task;
use App\Enumeration\Gender;
use DateTime;
use Exception;
use InvalidArgumentException;
use PDOException;

class TaskModel extends Model
{
    /**
     * Creates a Task instance from an associative array of database row data.
     *
     * This method converts raw database row data into a Task object, handling type conversions and
     * nested objects as needed:
     * - Converts date/time strings to DateTime objects
     * - Converts priority and status to their respective enum types
     * - Decodes the workers JSON and populates the WorkerContainer with Worker objects
     * - Converts worker publicId to UUID object
     * - Converts worker gender to Gender enum
     * - Converts worker jobTitles JSON to JobTitleContainer
     *
     * @param array $row Associative array containing task data with the following keys:
     *      - taskId: int Task ID
     *      - taskPublicId: string Task public identifier
     *      - taskName: string Task name
     *      - taskDescription: string Task description
     *      - taskWorkers: string JSON-encoded array of workers
     *      - taskStartDateTime: string Task start date/time (Y-m-d H:i:s)
     *      - taskCompletionDateTime: string Task expected completion date/time (Y-m-d H:i:s)
     *      - taskActualCompletionDateTime: string|null Actual completion date/time (Y-m-d H:i:s) or null
     *      - taskPriority: string|int Task priority (enum value)
     *      - taskStatus: string|int Task status (enum value)
     *      - taskCreatedAt: string Task creation timestamp (Y-m-d H:i:s)
     *
     * @return Task New Task instance created from provided data
     */
    private static function populate(array $row): Task {
        $task = Task::createPartial([
            'id'                        => $row['taskId'],
            'publicId'                  => $row['taskPublicId'],
            'name'                      => $row['taskName'],
            'description'               => $row['taskDescription'],
            'workers'                   => new WorkerContainer(),
            'startDateTime'             => new DateTime($row['taskStartDateTime']),
            'completionDateTime'        => new DateTime($row['taskCompletionDateTime']),
            'actualCompletionDateTime'  => $row['taskActualCompletionDateTime']
                ? new DateTime($row['taskActualCompletionDateTime'])
                : null,
            'priority'                  => TaskPriority::from($row['taskPriority']),
            'status'                    => WorkStatus::from($row['taskStatus']),
            'createdAt'                 => new DateTime($row['taskCreatedAt']),
        ]);

        $workers = json_decode($row['taskWorkers'], true);
        foreach ($workers as $worker) {
            $task->addWorker(Worker::createPartial([
                'id' => $worker['workerId'],
                'publicId' => UUID::fromHex($worker['workerPublicId']),
                'firstName' => $worker['workerFirstName'],
                'middleName' => $worker['workerMiddleName'],
                'lastName' => $worker['workerLastName'],
                'email' => $worker['workerEmail'],
                'contactNumber' => $worker['workerContactNumber'],
                'profileLink' => $worker['workerProfileLink'],
                'gender' => Gender::from($worker['workerGender']),
                'jobTitles' => isset($worker['workerJobTitles'])
                    ? new JobTitleContainer(json_decode($worker['workerJobTitles'], true))
                    : new JobTitleContainer()
            ]));
        }
        return $task;
    }

    /**
     * Finds and retrieves project tasks from the database with optional filtering and options.
     *
     * This method executes a complex SQL query to fetch project tasks along with their associated workers and worker job titles.
     * The result is returned as a TaskContainer containing Task objects populated with the retrieved data.
     * 
     * - Supports dynamic WHERE clauses and query options (e.g., ordering, limits).
     * - Aggregates worker information and their job titles as JSON arrays for each task.
     * - Handles empty result sets by returning null.
     * - Throws a DatabaseException on database errors.
     *
     * @param string $whereClause Optional SQL WHERE clause to filter tasks.
     * @param array $params Parameters to bind to the prepared SQL statement.
     * @param array $options Additional query options (e.g., order, limit).
     * 
     * @return TaskContainer|null A container of Task objects if found, or null if no tasks match the criteria.
     *
     * @throws DatabaseException If a PDOException occurs during query execution.
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?TaskContainer
    {
        $instance = new self();
        try {
            $projectTaskQueryString = "
                SELECT 
                    pt.id AS taskId,
                    pt.publicId AS taskPublicId,
                    pt.name AS taskName,
                    pt.description AS taskDescription,
                    pt.startDateTime AS taskStartDateTime,
                    pt.completionDateTime AS taskCompletionDateTime,
                    pt.actualCompletionDateTime AS taskActualCompletionDateTime,
                    pt.priority AS taskPriority,
                    pt.status AS taskStatus,
                    pt.createdAt AS taskCreatedAt,
                    COALESCE(
                        (
                            SELECT CONCAT('[', GROUP_CONCAT(
                                JSON_OBJECT(
                                    'workerId', u.id,
                                    'workerPublicId', HEX(u.publicId),
                                    'workerFirstName', u.firstName,
                                    'workerMiddleName', u.middleName,
                                    'workerLastName', u.lastName,
                                    'workerEmail', u.email,
                                    'workerContactNumber', u.contactNumber,
                                    'workerProfileLink', u.profileLink,
                                    'workerGender', u.gender,
                                    'workerJobTitles', COALESCE(
                                        (
                                            SELECT CONCAT('[', GROUP_CONCAT(CONCAT('\"', wjt.title, '\"')), ']')
                                            FROM userJobTitle wjt
                                            WHERE wjt.userId = u.id
                                        ),
                                        '[]'
                                    )
                                ) ORDER BY u.lastName SEPARATOR ','
                            ), ']')
                            FROM `projectTaskWorker` AS ptw
                            LEFT JOIN `user` AS u
                            ON ptw.workerId = u.id
                            WHERE ptw.id IS NOT NULL
                        ), '[]'
                    ) AS taskWorkers
                FROM 
                    `projectTask` AS pt
                LEFT JOIN 
                    `project` AS p ON pt.projectId = p.id
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

            $tasks = new TaskContainer();
            foreach ($results as $row) {
                $tasks->add(self::populate($row));
            }
            return $tasks;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * Retrieves all tasks associated with a given project ID.
     *
     * This method fetches tasks for a specific project, supporting both integer and UUID project identifiers.
     * It applies pagination options and returns a TaskContainer with the results.
     * - If $projectId is an integer, it is used directly in the query.
     * - If $projectId is a UUID, it is converted to binary and used in a subquery to resolve the internal project ID.
     *
     * @param int|UUID $projectId The project ID (integer or UUID as string).
     * @param array $options Optional query options:
     *      - offset: int (default 0) The starting point for the result set.
     *      - limit: int (default 10) The maximum number of tasks to return.
     *
     * @throws ValidationException If the provided project ID is invalid.
     * @throws DatabaseException If a database error occurs during retrieval.
     *
     * @return TaskContainer|null A container with the found tasks, or null if none found.
     */
    public static function findAllByProjectId(
        int|UUID $projectId,
        array $options = [
            'offset' => 0,
            'limit' => 10,
        ]
    ): ?TaskContainer {
        if ($projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        try {
            $whereClause = is_int($projectId) 
                        ? 'p.id = :projectId'
                        : 'p.id IN (
                            SELECT id 
                            FROM `project` 
                            WHERE publicId = :projectId)';
            $params = [
                ':projectId' => is_int($projectId) 
                    ? $projectId 
                    : UUID::toBinary($projectId)
            ];

            return self::find($whereClause,$params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds tasks assigned to a specific worker, optionally filtered by project.
     *
     * This method retrieves tasks assigned to the given worker, identified by either an integer ID or a UUID.
     * Optionally, results can be filtered by a specific project, also identified by an integer ID or a UUID.
     * Supports pagination through the $options parameter.
     *
     * @param int|UUID $workerId The worker's identifier (integer ID or UUID).
     * @param int|UUID|null $projectId (Optional) The project's identifier (integer ID or UUID). If null, tasks from all projects are included.
     * @param array $options (Optional) Query options:
     *      - offset: int (default 0) The number of records to skip.
     *      - limit: int (default 10) The maximum number of records to return.
     *
     * @throws ValidationException If the worker or project ID is invalid.
     * @throws Exception If an error occurs during the query.
     *
     * @return TaskContainer|null A container with the found tasks, or null if none found.
     */
    public static function findAssignedToWorker(
        int|UUID $workerId,
        int|UUID|null $projectId,
        array $options = [
            'offset' => 0,
            'limit' => 10,
        ]
    ): ?TaskContainer {
        if (is_int($workerId) && $workerId < 1) {
            throw new ValidationException('Invalid Worker ID');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        try {
            $whereClause = is_int($workerId) 
                ? 'u.id = :workerId'
                : 'u.publicId = :workerId';
            $params = [
                ':workerId' => is_int($workerId) 
                    ? $workerId 
                    : UUID::toBinary($workerId),
            ];

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.publicId = :projectId';
                $params[':projectId'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            return self::find($whereClause,$params, $options);
        } catch (Exception $e) {
            throw $e;
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
     * Finds and returns the count of tasks grouped by status for a specific project.
     *
     * This method queries the database to retrieve task counts grouped by their status
     * for a given project ID. It validates the input and handles database exceptions.
     *
     * @param int $projectId The unique identifier of the project to query tasks for
     * 
     * @return array|null Array of status counts where each element contains:
     *      - status: string The status of the tasks
     *      - count: int The number of tasks with that status
     *      Returns null if no tasks are found for the project
     * 
     * @throws ValidationException If the provided project ID is less than 1
     * @throws DatabaseException If a database error occurs during query execution
     */
    public static function findStatusCountByProjectId(int $projectId): ?array
    {
        if ($projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    pt.status AS taskStatus,
                    COUNT(*) AS taskCount
                FROM 
                    `projectTask` AS pt
                WHERE 
                    pt.projectId = :projectId
                GROUP BY 
                    pt.status";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':projectId' => $projectId]);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            $statusCounts = [];
            foreach ($results as $row) {
                $statusCounts[$row['taskStatus']] = (int)$row['taskCount'];
            }

            return $statusCounts;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds and returns the count of tasks grouped by priority for a specific project.
     *
     * This method retrieves task distribution statistics by querying the database
     * for all tasks associated with the given project ID and groups them by their
     * priority level. The results include the priority value and the number of
     * tasks for each priority.
     *
     * @param int $projectId The unique identifier of the project to query
     * 
     * @return array|null Array of associative arrays containing priority counts, or null if no tasks found.
     *      Each array element contains:
     *      - priority: string The priority level of the tasks
     *      - count: int The number of tasks with this priority
     * 
     * @throws ValidationException If the provided project ID is less than 1
     * @throws DatabaseException If a database error occurs during query execution
     */
    public static function findPriorityCountByProjectId(int $projectId): ?array
    {
        if ($projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    pt.priority AS taskPriority,
                    COUNT(*) AS taskCount
                FROM 
                    `projectTask` AS pt
                WHERE 
                    pt.projectId = :projectId
                GROUP BY 
                    pt.priority";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':projectId' => $projectId]);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            $priorityCounts = [];
            foreach ($results as $row) {
                $priorityCounts[$row['taskPriority']] = (int)$row['taskCount'];
            }

            return $priorityCounts;
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
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function save(array $data): bool
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $updateFields = [];
            $params = [':id' => $data['id']];

            if (isset($data['name'])) {
                $updateFields[] = 'name = :name';
                $params[':name'] = trimOrNull($data['name']);
            }

            if (isset($data['description'])) {
                $updateFields[] = 'description = :description';
                $params[':description'] = trimOrNull($data['description']);
            }

            if (isset($data['status'])) {
                $updateFields[] = 'status = :status';
                $params[':status'] = $data['status']->value;
            }

            if (isset($data['startDateTime'])) {
                $updateFields[] = 'startDateTime = :startDateTime';
                $params[':startDateTime'] = formatDateTime($data['startDateTime'], DateTime::ATOM);
            }

            if (isset($data['completionDateTime'])) {
                $updateFields[] = 'completionDateTime = :completionDateTime';
                $params[':completionDateTime'] = formatDateTime($data['completionDateTime'], DateTime::ATOM);
            }

            if (isset($data['actualCompletionDateTime'])) {
                $updateFields[] = 'actualCompletionDateTime = :actualCompletionDateTime';
                $params[':actualCompletionDateTime'] = $data['actualCompletionDateTime'] !== null 
                    ? formatDateTime($data['actualCompletionDateTime'], DateTime::ATOM) 
                    : null;
            }

            if (!empty($updateFields)) {
                $projectQuery = "UPDATE `projectTask` SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $statement = $instance->connection->prepare($projectQuery);
                $statement->execute($params);
            }

            if ($data['workers'] && $data['workers'] instanceof WorkerContainer) {
                foreach ($data['workers'] as $worker) {
                    $worker->save();
                }
            }

            $instance->connection->commit();
            return true;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Deletes a task from the data source.
     *
     * This method is currently not implemented as there is no use case for deleting tasks.
     * Always returns false.
     *
     * @return bool Returns false, indicating the operation is not supported.
     */
    public static function delete(): bool
    {
        // Not implemented (No use case)
        return false;
    }









    public static function create(mixed $data): mixed
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of TaskModel');
        }
        return null;
    }
}