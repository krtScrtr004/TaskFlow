<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\JobTitleContainer;
use App\Container\WorkerContainer;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Entity\Project;
use App\Exception\ValidationException;
use App\Exception\DatabaseException;
use App\Model\UserModel;
use App\Enumeration\WorkStatus;
use App\Enumeration\TaskPriority;
use App\Entity\User;
use App\Entity\Task;
use App\Enumeration\Gender;
use App\Enumeration\WorkerStatus;
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

        $workers = json_decode($row['taskWorkers'], true) ?? [];
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
                'status' => WorkerStatus::from($worker['workerStatus']),
                'jobTitles' => isset($worker['workerJobTitles'])
                    ? new JobTitleContainer(json_decode($worker['workerJobTitles'], true))
                    : new JobTitleContainer(),
                'additionalInfo' => [
                    'totalTasks' => (int)$worker['workerTotalTasks'],
                    'completedTasks' => (int)$worker['workerCompletedTasks']
                ]
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
            $query = "
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
                                    'workerStatus', ptw.status,
                                    'workerJobTitles', COALESCE(
                                        (
                                            SELECT CONCAT('[', GROUP_CONCAT(CONCAT('\"', wjt.title, '\"')), ']')
                                            FROM userJobTitle wjt
                                            WHERE wjt.userId = u.id
                                        ),
                                        '[]'
                                    ),
                                    'workerTotalTasks', (
                                        SELECT COUNT(*)
                                        FROM projectTaskWorker ptw2
                                        WHERE ptw2.workerId = u.id
                                    ),
                                    'workerCompletedTasks', (
                                        SELECT COUNT(*)
                                        FROM projectTaskWorker ptw3
                                        INNER JOIN projectTask pt3 ON ptw3.taskId = pt3.id
                                        WHERE ptw3.workerId = u.id
                                        AND pt3.status = 'completed'
                                    )
                                ) ORDER BY u.lastName SEPARATOR ','
                            ), ']')
                            FROM `projectTaskWorker` AS ptw
                            INNER JOIN `user` AS u
                            ON ptw.workerId = u.id
                            WHERE ptw.taskId = pt.id
                        ), '[]'
                    ) AS taskWorkers
                FROM 
                    `projectTask` AS pt
                INNER JOIN 
                    `project` AS p 
                ON 
                    pt.projectId = p.id
                LEFT JOIN 
                    `projectTaskWorker` AS ptw 
                ON 
                    pt.id = ptw.taskId
                LEFT JOIN 
                    `user` AS u ON ptw.workerId = u.id
            ";
            $projectTaskQuery = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($query, $whereClause),
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
     * Searches for tasks matching the provided search key within a project.
     *
     * This method performs a full-text search on the task name and description fields.
     * Optionally, the search can be limited to a specific project by providing a project ID.
     * Supports pagination through limit and offset options.
     *
     * @param string $key The search keyword to match against task name and description.
     * @param int|UUID|null $userId (optional) The user ID or UUID to filter tasks created by / assigned to a specific user. If null, searches across all users.
     * @param int|UUID|null $projectId (optional) The project ID or UUID to filter tasks by project. If null, searches across all projects.
     * @param WorkStatus|TaskPriority|null $filter (optional) Filter to apply on task status or priority.
     * @param array $options (optional) Search options:
     *      - limit: int Maximum number of results to return (default: 10)
     *      - offset: int Number of results to skip for pagination (default: 0)
     *
     * @throws InvalidArgumentException If the search key is empty.
     * @throws Exception If an error occurs during the search operation.
     *
     * @return TaskContainer|null A container of found tasks, or null if no tasks match the search criteria.
     */
    public static function search( string $key,
        int|UUID|null $userId = null,
        int|UUID|null $projectId = null,
        WorkStatus|TaskPriority|null $filter = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]): ?TaskContainer 
    {
        if (trimOrNull($key) === null) {
            throw new InvalidArgumentException('Search key cannot be empty.');
        }

        try {
            $params = [];

            $whereClause = "MATCH(pt.name, pt.description) AGAINST (:key IN NATURAL LANGUAGE MODE)";
            $params = [':key' => $key];
            $options = [
                ':limit'    => $options['limit'] ?? 10,
                ':offset'   => $options['offset'] ?? 0
            ];

            // Filter by user role if provided
            if ($userId) {
                $whereClause .= is_int($userId) 
                    ? ' AND (p.managerId = :userId1
                        OR pt.id IN (
                            SELECT ptw.taskId 
                            FROM projectTaskWorker ptw 
                            WHERE ptw.workerId = :userId2)
                        )'
                    : ' AND (p.managerId IN (
                            SELECT id
                            FROM `user` 
                            WHERE publicId = :userId1)
                        pt.id IN (
                            SELECT ptw.taskId 
                            FROM projectTaskWorker ptw 
                            INNER JOIN `user` u ON ptw.workerId = u.id
                            WHERE u.publicId = :userId2)
                        )';
                $params[':userId1'] = is_int($userId) 
                    ? $userId 
                    : UUID::toBinary($userId);
                $params[':userId2'] = $params[':userId1'];
            }

            // Narrow by project if provided
            if ($projectId !== null) {
                $whereClause .= is_int($projectId) 
                    ? ' AND pt.projectId = :projectId'
                    : ' AND pt.projectId IN (
                        SELECT id 
                        FROM `project` 
                        WHERE publicId = :projectId)';
                $params[':projectId'] = is_int($projectId) 
                    ? $projectId 
                    : UUID::toBinary($projectId);
            }

            // Apply status / priority filter if provided
            if ($filter instanceof WorkStatus) {
                $whereClause .= ' AND pt.status = :status';
                $params[':status'] = $filter->value;
            } elseif ($filter instanceof TaskPriority) {
                $whereClause .= ' AND pt.priority = :priority';
                $params[':priority'] = $filter->value;
            }

            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds a Task by its ID and optionally by Project ID.
     *
     * This method retrieves a Task instance from the database using either its internal integer ID or its public UUID.
     * Optionally, the search can be restricted to a specific project by providing a project ID (integer or UUID).
     * The method validates the provided IDs and constructs the appropriate SQL WHERE clause and parameters.
     *
     * @param int|UUID $taskId The task's internal integer ID or public UUID.
     * @param int|UUID|null $projectId (optional) The project's internal integer ID or public UUID to further filter the task.
     *
     * @throws ValidationException If the provided task or project ID is invalid (e.g., less than 1 for integers).
     * @throws Exception If an error occurs during the database query.
     *
     * @return Task|null The found Task instance, or null if no matching task is found.
     */
    public static function findById(int|UUID $taskId, int|UUID|null $projectId = null): ?Task {
        if (is_int($taskId) && $taskId < 1) {
            throw new ValidationException('Invalid Task ID');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        try {
            $whereClause = is_int($taskId) 
                ? 'pt.id = :taskId' 
                : 'pt.publicId = :taskId';
            $params = [
                ':taskId' => is_int($taskId) 
                    ? $taskId 
                    : UUID::toBinary($taskId)
            ];

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.publicId = :projectId';
                $params[':projectId'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            $tasks = self::find($whereClause, $params);
            return $tasks->first() ?? null;
        } catch (Exception $e) {
            throw $e;
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
     * @param WorkStatus|TaskPriority|null $filter Optional filter to narrow down tasks by status or priority.
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
        WorkStatus|TaskPriority|null $filter = null,
        array $options = [
            'offset' => 0,
            'limit' => 10,
        ]
    ): ?TaskContainer {
        if (is_int($projectId) && $projectId < 1) {
            throw new ValidationException('Invalid Project ID');
        }

        try {
            $whereClause = is_int($projectId) 
                ? 'pt.projectId = :projectId'
                : 'pt.projectId IN (
                    SELECT id 
                    FROM `project` 
                    WHERE publicId = :projectId)';
            $params = [
                ':projectId' => is_int($projectId) 
                    ? $projectId 
                    : UUID::toBinary($projectId)
            ];

            if ($filter instanceof WorkStatus) {
                $whereClause .= ' AND pt.status = :status';
                $params[':status'] = $filter->value;
            } elseif ($filter instanceof TaskPriority) {
                $whereClause .= ' AND pt.priority = :priority';
                $params[':priority'] = $filter->value;
            }

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
     * @param WorkStatus|TaskPriority|null $filter Optional filter to narrow down tasks by status or priority.
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
        WorkStatus|TaskPriority|null $filter = null,
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

            if ($filter instanceof WorkStatus) {
                $whereClause .= ' AND pt.status = :status';
                $params[':status'] = $filter->value;
            } elseif ($filter instanceof TaskPriority) {
                $whereClause .= ' AND pt.priority = :priority';
                $params[':priority'] = $filter->value;
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
     * Finds tasks by their work status, optionally filtered by project.
     *
     * This method retrieves a collection of tasks that match the specified work status.
     * Optionally, tasks can be filtered by a specific project, identified by either an integer ID or a UUID.
     * The method supports pagination through the 'limit' and 'offset' options.
     *
     * @param WorkStatus $status The work status to filter tasks by.
     * @param int|UUID|null $projectId (optional) The project identifier. Can be an integer ID, a UUID, or null to include all projects.
     * @param array $options (optional) Query options:
     *      - limit: int (default 10) Maximum number of tasks to return.
     *      - offset: int (default 0) Number of tasks to skip before starting to collect the result set.
     *
     * @throws InvalidArgumentException If an invalid project ID is provided.
     * @throws Exception If an error occurs during the query.
     *
     * @return TaskContainer|null A container of tasks matching the criteria, or null if none found.
     */
    public static function findByStatus(
        WorkStatus $status,
        int|UUID|null $projectId = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?TaskContainer
    {
        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            $whereClause = 'pt.status = :status';
            $params = [':status' => $status->value];

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.publicId = :projectId';
                $params[':projectId'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds tasks by their priority, optionally filtered by project.
     *
     * This method retrieves a collection of tasks that match the specified priority.
     * If a project ID is provided, the search is limited to tasks within that project.
     * The project ID can be either an integer (internal ID) or a UUID (public ID).
     * Additional options such as limit and offset can be specified for pagination.
     *
     * @param TaskPriority $priority The priority level to filter tasks by.
     * @param int|UUID|null $projectId (optional) The project identifier. Accepts:
     *      - int: Internal project ID
     *      - UUID: Public project UUID
     *      - null: No project filter applied
     * @param array $options (optional) Query options:
     *      - limit: int Maximum number of tasks to return (default: 10)
     *      - offset: int Number of tasks to skip (default: 0)
     *
     * @throws InvalidArgumentException If an invalid project ID is provided.
     * @throws Exception If an error occurs during the query.
     *
     * @return TaskContainer|null A container of found tasks, or null if none found.
     */
    public static function findByPriority(
        TaskPriority $priority,
        int|UUID|null $projectId = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?TaskContainer
    {
        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            $whereClause = 'pt.priority = :priority';
            $params = [':priority' => $priority->value];

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.publicId = :projectId';
                $params[':projectId'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
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
     * Finds and returns the Project that owns a given Task.
     *
     * This method retrieves the project associated with the specified task ID (either integer or UUID).
     * It joins the project, user (manager), and projectTask tables to fetch project details and its manager's information.
     * Returns a partial Project instance with the manager as a partial User instance, or null if not found.
     *
     * @param int|UUID $taskId The ID or public UUID of the task whose owning project is to be found.
     *
     * @throws ValidationException If the provided task ID is invalid.
     * @throws DatabaseException If a database error occurs during the query.
     *
     * @return Project|null The owning Project instance, or null if no project is found for the given task.
     */
    public static function findOwningProject(int|UUID $taskId): ?Project
    {
        if (is_int($taskId) && $taskId < 1) {
            throw new ValidationException('Invalid Task ID');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    p.*,
                    u.id AS managerId,
                    u.publicId AS managerPublicId,
                    u.firstName AS managerFirstName,
                    u.middleName AS managerMiddleName,
                    u.lastName AS managerLastName,
                    u.gender AS managerGender,
                    u.email AS managerEmail,
                    u.profileLink AS managerProfileLink 
                FROM 
                    `project` AS p
                INNER JOIN
                    `user` AS u 
                ON 
                    p.managerId = u.id
                LEFT JOIN 
                    `projectTask` AS pt
                ON
                    p.id = pt.projectId
                WHERE 
                    " . (is_int($taskId) 
                        ? 'pt.id = :taskId' 
                        : 'pt.publicId = :taskId');
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':taskId' => is_int($taskId) 
                    ? $taskId 
                    : UUID::toBinary($taskId)
            ]);
            $result = $statement->fetch();

            if (!$instance->hasData($result)) {
                return null;
            }

            $project = Project::createPartial([
                'id'                        => $result['id'],
                'publicId'                  => $result['publicId'],
                'name'                      => $result['name'],
                'description'               => $result['description'],
                'budget'                    => $result['budget'],
                'status'                    => $result['status'],
                'startDateTime'             => new DateTime($result['startDateTime']),
                'completionDateTime'        => new DateTime($result['completionDateTime']),
                'actualCompletionDateTime'  => new DateTime($result['actualCompletionDateTime']),
                'createdAt'                 => new DateTime($result['createdAt']),
                'manager'                   => User::createPartial([
                    'id'           => $result['managerId'],
                    'publicId'     => $result['managerPublicId'],
                    'firstName'    => $result['managerFirstName'],
                    'middleName'   => $result['managerMiddleName'],
                    'lastName'     => $result['managerLastName'],
                    'gender'       => $result['managerGender'],
                    'email'        => $result['managerEmail'],
                    'profileLink'  => $result['managerProfileLink'],
                ]),
            ]);
            return $project;
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
            $options = [
                'offset'    => $offset,
                'limit'     => $limit,
            ];
            return self::find('', [], $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Inserts a new Task into the database and assigns workers to it.
     *
     * This method performs the following operations within a transaction:
     * - Validates that the provided object is an instance of Task.
     * - Generates or retrieves the publicId for the task.
     * - Extracts and formats task properties (name, description, priority, status, start and completion dates).
     * - Inserts the task into the `projectTask` table.
     * - Assigns workers to the task by inserting records into the `projectTaskWorker` table.
     * - Commits the transaction if all operations succeed.
     * - Rolls back the transaction and throws an exception if any error occurs.
     *
     * @param Task $task The Task object to be inserted. Must provide:
     *      - projectId: string|int Project identifier (from additional info)
     *      - publicId: string|UUID|null Task public identifier (optional)
     *      - name: string|null Task name
     *      - description: string|null Task description
     *      - priority: TaskPriority Task priority enum
     *      - status: TaskStatus Task status enum
     *      - workers: WorkerContainer Container of Worker objects assigned to the task
     *      - startDateTime: DateTimeInterface|null Task start date and time
     *      - completionDateTime: DateTimeInterface|null Task completion date and time
     *
     * @throws InvalidArgumentException If the provided object is not a Task instance.
     * @throws DatabaseException If a PDOException occurs during database operations.
     * @throws Exception For any other errors during the process.
     *
     * @return Task The Task object with updated id and publicId after successful insertion.
     */
    public static function create(mixed $task): mixed
    {
        if (!($task instanceof Task)) {
            throw new InvalidArgumentException('Expected instance of Task');
        }

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $projectId          = $task->getAdditionalInfo('projectId');
            $taskPublicId       = $task->getPublicId() ?? UUID::get();
            $taskName           = trimOrNull($task->getName());
            $taskDescription    = trimOrNull($task->getDescription());
            $taskPriority       = $task->getPriority()->value;
            $taskStatus         = $task->getStatus()->value;
            $taskWorkers        = $task->getWorkers()->getItems();
            $taskStartDateTime  = formatDateTime($task->getStartDateTime());
            $completionDateTime = formatDateTime($task->getCompletionDateTime());

            $taskQuery = "
                INSERT INTO `projectTask` (
                    publicId, 
                    projectId,
                    name, 
                    description, 
                    priority, 
                    status, 
                    startDateTime, 
                    completionDateTime
                ) VALUES (
                    :publicId, 
                    :projectId,
                    :name, 
                    :description, 
                    :priority, 
                    :status, 
                    :startDateTime, 
                    :completionDateTime
                )
            ";
            $statement = $instance->connection->prepare($taskQuery);
            $statement->execute([
                ':publicId'         => UUID::toBinary($taskPublicId),
                ':projectId'        => $projectId,
                ':name'             => $taskName,
                ':description'      => $taskDescription,
                ':priority'         => $taskPriority,
                ':status'           => $taskStatus,
                ':startDateTime'    => $taskStartDateTime,
                ':completionDateTime'=> $completionDateTime,
            ]);
            $taskId = (int)$instance->connection->lastInsertId();

            if ($taskWorkers && count($taskWorkers) > 0) {
                $taskWorkerQuery = "
                    INSERT INTO `projectTaskWorker` (
                        taskId,
                        workerId,
                        status
                    ) SELECT :taskId, id, :status
                    FROM `user`
                    WHERE publicId = :workerId";
                $workerStatement = $instance->connection->prepare($taskWorkerQuery);
                foreach ($taskWorkers as $worker) {
                    $workerStatement->execute([
                        ':taskId'   => $taskId instanceof UUID ? UUID::toBinary($taskId) : $taskId,
                        ':workerId' => UUID::toBinary($worker->getPublicId()),
                        ':status'   => WorkerStatus::ASSIGNED->value,
                    ]);
                }
            }

            $instance->connection->commit();

            $task->setId($taskId);
            $task->setPublicId($taskPublicId);
            return $task;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        } catch (Exception $e) {
            $instance->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Updates an existing task in the database with the provided data.
     *
     * This method updates the fields of a task record in the `projectTask` table based on the keys present in the input array.
     * It also saves associated worker data if provided.
     * The update is performed within a transaction to ensure data integrity.
     *
     * @param array $data Associative array containing task data with the following keys:
     *      - id: int Task ID (required)
     *      - name: string|null Task name (optional)
     *      - description: string|null Task description (optional)
     *      - status: TaskStatusEnum Task status enum (optional)
     *      - startDateTime: string|DateTime Task start date and time (optional)
     *      - completionDateTime: string|DateTime Task scheduled completion date and time (optional)
     *      - actualCompletionDateTime: string|DateTime|null Actual completion date and time (optional)
     *      - workers: WorkerContainer|null Container of worker objects associated with the task (optional)
     *
     * @throws DatabaseException If a database error occurs during the update process.
     * @return bool True on successful update, false otherwise.
     */
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

            if (isset($data['priority'])) {
                $updateFields[] = 'priority = :priority';
                $params[':priority'] = $data['priority']->value;
            }

            if (isset($data['status'])) {
                $updateFields[] = 'status = :status';
                $params[':status'] = $data['status']->value;
            }

            if (isset($data['startDateTime'])) {
                $updateFields[] = 'startDateTime = :startDateTime';
                $params[':startDateTime'] = formatDateTime($data['startDateTime']);
            }

            if (isset($data['completionDateTime'])) {
                $updateFields[] = 'completionDateTime = :completionDateTime';
                $params[':completionDateTime'] = formatDateTime($data['completionDateTime']);
            }

            if (isset($data['actualCompletionDateTime'])) {
                $updateFields[] = 'actualCompletionDateTime = :actualCompletionDateTime';
                $params[':actualCompletionDateTime'] = $data['actualCompletionDateTime'] !== null 
                    ? formatDateTime($data['actualCompletionDateTime']) 
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
}