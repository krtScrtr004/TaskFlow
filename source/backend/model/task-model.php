<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\JobTitleContainer;
use App\Container\WorkerContainer;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Entity\Project;
use App\Exception\DatabaseException;
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
     * Finds and retrieves tasks from the database based on specified criteria.
     *
     * This method executes a complex SQL query to fetch tasks along with their associated phase,
     * project, and worker information. It supports dynamic WHERE clauses, query parameters, and
     * additional query options for flexible searching.
     *
     * The returned data includes:
     * - Task details (ID, public ID, name, description, dates, priority, status, creation timestamp)
     * - Associated phase public ID
     * - Workers assigned to the task, including:
     *      - Worker ID, public ID, name, email, contact number, profile link, gender, status, creation timestamp, confirmation timestamp, deletion timestamp
     *      - Job titles (as an array)
     *      - Total tasks assigned to the worker
     *      - Completed tasks by the worker
     *
     * @param string $whereClause Optional SQL WHERE clause for filtering tasks.
     * @param array $params Parameters to bind to the prepared statement for the query.
     * @param array $options Additional options to modify the query (e.g., sorting, limiting).
     *
     * @return TaskContainer|null A container of Task objects matching the criteria, or null if none found.
     *
     * @throws DatabaseException If a database error occurs during query execution.
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?TaskContainer
    {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'pt.id',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'pt.start_date_time DESC',
        ];

        $instance = new self();
        try {
            $query = "
                SELECT 
                    pt.id AS id,
                    pt.public_id AS public_id,
                    pp.public_id AS phase_id,
                    pt.name AS name,
                    pt.description AS description,
                    pt.start_date_time AS start_date_time,
                    pt.completion_date_time AS completion_date_time,
                    pt.actual_completion_date_time AS actual_completion_date_time,
                    pt.priority AS priority,
                    pt.status AS status,
                    pt.created_at AS created_at,
                    COALESCE(
                        (
                            SELECT CONCAT('[', GROUP_CONCAT(
                                JSON_OBJECT(
                                    'id', u.id,
                                    'public_id', HEX(u.public_id),
                                    'first_name', u.first_name,
                                    'middle_name', u.middle_name,
                                    'last_name', u.last_name,
                                    'email', u.email,
                                    'contact_number', u.contact_number,
                                    'profile_link', u.profile_link,
                                    'gender', u.gender,
                                    'status', ptw.status,
                                    'created_at', u.created_at,
                                    'confirmed_at', u.confirmed_at,
                                    'deleted_at', u.deleted_at,
                                    'job_titles', COALESCE(
                                        (
                                            SELECT 
                                                CONCAT('[', GROUP_CONCAT(CONCAT('\"', wjt.title, '\"')), ']')
                                            FROM 
                                                `user_job_title` AS wjt
                                            WHERE 
                                                wjt.user_id = u.id
                                        ),
                                        '[]'
                                    ),
                                    'worker_total_tasks', (
                                        SELECT 
                                            COUNT(*)
                                        FROM 
                                            `phase_task_worker` AS ptw2
                                        WHERE 
                                            ptw2.worker_id = u.id
                                    ),
                                    'worker_completed_tasks', (
                                        SELECT 
                                            COUNT(*)
                                        FROM 
                                            `phase_task_worker` AS ptw3
                                        INNER JOIN 
                                            `phase_task` AS pt3 
                                        ON
                                            ptw3.task_id = pt3.id
                                        WHERE 
                                            ptw3.worker_id = u.id
                                        AND 
                                            pt3.status = 'completed'
                                    )
                                ) ORDER BY u.last_name ASC SEPARATOR ','
                            ), ']')
                            FROM 
                                `phase_task_worker` AS ptw
                            INNER JOIN 
                                `user` AS u
                            ON 
                                ptw.worker_id = u.id
                            WHERE 
                                ptw.task_id = pt.id
                        ), '[]'
                    ) AS task_workers
                FROM 
                    `phase_task` AS pt
                INNER JOIN 
                    `project_phase` AS pp 
                ON 
                    pt.phase_id = pp.id
                INNER JOIN 
                    `project` AS p 
                ON 
                    pp.project_id = p.id
                LEFT JOIN 
                    `phase_task_worker` AS ptw 
                ON 
                    pt.id = ptw.task_id
                LEFT JOIN 
                    `user` AS u ON ptw.worker_id = u.id
            ";
            $projectTaskQuery = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($query, $whereClause),
                $paramOptions);

            $statement = $instance->connection->prepare($projectTaskQuery);
            $statement->execute($params);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            $tasks = new TaskContainer();
            foreach ($results as $row) {
                $task = Task::createPartial([
                    'id'                        => $row['id'],
                    'publicId'                  => $row['public_id'],
                    'name'                      => $row['name'],
                    'description'               => $row['description'],
                    'workers'                   => new WorkerContainer(),
                    'startDateTime'             => new DateTime($row['start_date_time']),
                    'completionDateTime'        => new DateTime($row['completion_date_time']),
                    'actualCompletionDateTime'  => $row['actual_completion_date_time']
                        ? new DateTime($row['actual_completion_date_time'])
                        : null,
                    'priority'                  => TaskPriority::from($row['priority']),
                    'status'                    => WorkStatus::from($row['status']),
                    'createdAt'                 => new DateTime($row['created_at']),
                    'additionalInfo'            => ['phaseId' => UUID::fromBinary($row['phase_id'])]
                ]);

                $workers = json_decode($row['task_workers'], true) ?? [];
                foreach ($workers as $worker) {
                    $task->addWorker(Worker::createPartial([
                        'id'                => $worker['id'],
                        'publicId'          => UUID::fromHex($worker['public_id']),
                        'firstName'         => $worker['first_name'],
                        'middleName'        => $worker['middle_name'],
                        'lastName'          => $worker['last_name'],
                        'email'             => $worker['email'],
                        'contactNumber'     => $worker['contact_number'],
                        'profileLink'       => $worker['profile_link'],
                        'gender'            => Gender::from($worker['gender']),
                        'status'            => WorkerStatus::from($worker['status']),
                        'jobTitles'         => isset($worker['job_titles'])
                            ? new JobTitleContainer(json_decode($worker['job_titles'], true))
                            : new JobTitleContainer(),
                        'createdAt'         => $worker['created_at'],
                        'confirmedAt'       => $worker['confirmed_at'],
                        'deletedAt'         => $worker['deleted_at'],
                        'additionalInfo'    => [
                            'totalTasks'        => (int)$worker['worker_total_tasks'],
                            'completedTasks'    => (int)$worker['worker_completed_tasks']
                        ]
                    ]));
                }
                $tasks->add($task);
            }
            return $tasks;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * Searches for tasks based on various criteria.
     *
     * This method allows searching for tasks using a keyword, user, phase, project, and optional status or priority filters.
     * It builds a dynamic SQL WHERE clause based on provided parameters and supports pagination via limit and offset options.
     *
     * @param string $key Search keyword to match against task name and description.
     * @param int|UUID|null $userId User ID or UUID to filter tasks by manager or worker.
     * @param int|UUID|null $phaseId Phase ID or UUID to narrow tasks by project phase.
     * @param int|UUID|null $projectId Project ID or UUID to filter tasks by project.
     * @param WorkStatus|TaskPriority|null $filter Optional filter for task status or priority.
     * @param array $options Pagination options:
     *      - limit: int Maximum number of results to return (default: 10)
     *      - offset: int Number of results to skip (default: 0)
     *
     * @return TaskContainer|null Container of found tasks, or null if no tasks match the criteria.
     *
     * @throws InvalidArgumentException If an invalid ID is / are provided.
     * @throws Exception If an error occurs during the search operation.
     */
    public static function search( 
        string $key = '',
        int|UUID|null $userId = null,
        int|UUID|null $phaseId = null,
        int|UUID|null $projectId = null,
        WorkStatus|TaskPriority|null $filter = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?TaskContainer {
        if ($userId && is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        if ($phaseId && is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID.');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID.');
        }

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $where = [];
            $params = [];

            if (trimOrNull($key)) {
                $where[] = "MATCH(pt.name, pt.description) AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Filter by user role if provided
            if ($userId) {
                $where[] = is_int($userId) 
                    ? ' (p.manager_id = :userId1 OR ptw.worker_id = :userId2)'
                    : ' (ptw.worker_id IN (
                            SELECT 
                                id
                            FROM 
                                `user` 
                            WHERE 
                                public_id = :userId1)
                        OR 
                            p.manager_id IN (
                                SELECT 
                                    id
                                FROM 
                                    `user`
                                WHERE 
                                    public_id = :userId2)
                        )';
                $params[':userId1'] = is_int($userId) 
                    ? $userId 
                    : UUID::toBinary($userId);
                $params[':userId2'] = is_int($userId) 
                    ? $userId 
                    : UUID::toBinary($userId);
            }

            // Narrow by phase if provided
            if ($phaseId !== null) {
                $where[] = is_int($phaseId) 
                    ? ' pt.phase_id = :phaseId'
                    : ' pt.phase_id IN (
                        SELECT 
                            id 
                        FROM 
                            `project_phase` 
                        WHERE 
                            public_id = :phaseId)';
                $params[':phaseId'] = is_int($phaseId) 
                    ? $phaseId 
                    : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $where[] = is_int($projectId) 
                    ? ' p.id = :projectId'
                    : ' p.public_id = :projectId';
                $params[':projectId'] = is_int($projectId) 
                    ? $projectId 
                    : UUID::toBinary($projectId);
            }

            // Apply status / priority filter if provided
            if ($filter instanceof WorkStatus) {
                $where[] = ' pt.status = :status';
                $params[':status'] = $filter->value;
            } elseif ($filter instanceof TaskPriority) {
                $where[] = ' pt.priority = :priority';
                $params[':priority'] = $filter->value;
            }

            $whereClause = implode(' AND ', $where);
            return self::find($whereClause, $params, $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds a Task by its ID, optionally filtered by Phase ID and Project ID.
     *
     * This method supports both integer and UUID identifiers for tasks, phases, and projects.
     * It validates the provided IDs and constructs the appropriate query to retrieve the task.
     * If no matching task is found, it returns null.
     *
     * @param int|UUID $taskId The unique identifier of the task (integer or UUID).
     * @param int|UUID|null $phaseId (optional) The unique identifier of the phase (integer or UUID).
     * @param int|UUID|null $projectId (optional) The unique identifier of the project (integer or UUID).
     *
     * @throws InvalidArgumentException If any provided ID is invalid (e.g., less than 1 for integers).
     * @throws Exception If an error occurs during the query execution.
     *
     * @return Task|null The found Task instance, or null if no matching task exists.
     */
    public static function findById(
        int|UUID $taskId, 
        int|UUID|null $phaseId = null, 
        int|UUID|null $projectId = null
    ): ?Task {
        if (is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID.');
        }

        if ($phaseId && is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID.');
        }

        try {
            $whereClause = is_int($taskId) 
                ? 'pt.id = :taskId' 
                : 'pt.public_id = :taskId';
            $params = [
                ':taskId' => is_int($taskId) 
                    ? $taskId 
                    : UUID::toBinary($taskId)
            ];

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pp.id = :phaseId'
                    : ' AND pp.public_id = :phaseId';
                $params[':phaseId'] = is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.public_id = :projectId';
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
     * Retrieves all tasks associated with a specific phase ID, with optional filtering by project ID and status or priority.
     *
     * This method constructs a dynamic SQL WHERE clause based on the provided parameters:
     * - Supports both integer and UUID formats for phase_id and projectId.
     * - Filters tasks by phase, and optionally by project, status, or priority.
     * - Handles conversion of UUIDs to binary format for database queries.
     * - Applies pagination options such as offset and limit.
     *
     * @param int|UUID $phaseId The phase identifier (integer or UUID) to filter tasks by.
     * @param int|UUID|null $projectId (optional) The project identifier (integer or UUID) to further filter tasks.
     * @param WorkStatus|TaskPriority|null $filter (optional) Filter tasks by work status or priority.
     * @param array $options (optional) Pagination options:
     *      - offset: int The starting index for results (default: 0).
     *      - limit: int The maximum number of results to return (default: 10).
     *
     * @throws InvalidArgumentException If the provided phase_id is invalid.
     * @throws Exception If an error occurs during query execution.
     *
     * @return TaskContainer|null A container of tasks matching the criteria, or null if none found.
     */
    public static function findAllByPhaseId(
        int|UUID $phaseId,
        int|UUID|null $projectId = null,
        WorkStatus|TaskPriority|null $filter = null,
        array $options = [
            'offset' => 0,
            'limit' => 10,
        ]
    ): ?TaskContainer {
        if (is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID.');
        }

        try {
            $whereClause = is_int($phaseId) 
                ? 'pt.phase_id = :phaseId'
                : 'pt.phase_id IN (
                    SELECT 
                        id 
                    FROM 
                        `project_phase` 
                    WHERE 
                        public_id = :phaseId)';
            $params = [
                ':phaseId' => is_int($phaseId) 
                    ? $phaseId 
                    : UUID::toBinary($phaseId)
            ];

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.public_id = :projectId';
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
     * Finds tasks assigned to a specific worker, optionally filtered by project (through phases).
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
     * @throws InvalidArgumentException If the worker or project ID is invalid.
     * @throws Exception If an error occurs during the query.
     *
     * @return TaskContainer|null A container with the found tasks, or null if none found.
     */
    public static function findAssignedToWorker(
        int|UUID $workerId,
        int|UUID|null $projectId = null,
        WorkStatus|TaskPriority|null $filter = null,
        array $options = [
            'offset' => 0,
            'limit' => 10,
        ]
    ): ?TaskContainer {
        if (is_int($workerId) && $workerId < 1) {
            throw new InvalidArgumentException('Invalid worker ID.');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID.');
        }

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $whereClause = is_int($workerId) 
                ? 'u.id = :workerId'
                : 'u.public_id = :workerId';
            $params = [
                ':workerId' => is_int($workerId) 
                    ? $workerId 
                    : UUID::toBinary($workerId),
            ];

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.public_id = :projectId';
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

            return self::find($whereClause,$params, $paramOptions);
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
     * 
     * @return WorkerContainer|null A container with all workers assigned to the task, or null if no workers are found
     * 
     * @throws InvalidArgumentException If the task ID is less than 1
     * @throws DatabaseException If a database error occurs during the query
     */
    public static function findWorkersByTaskId(int $taskId): ?WorkerContainer
    {
        if ($taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID.');
        }

        $instance = new self();
        try {
            $taskWorkerQuery = "
                SELECT 
                    u.id,
                    u.public_id,
                    u.first_name,
                    u.middle_name,
                    u.last_name,
                    u.profile_link
                FROM 
                    `user` AS u
                INNER JOIN 
                    `projectTaskWorker` AS tw 
                ON 
                    u.id = tw.worker_id
                WHERE 
                    tw.task_id = :taskId
                ORDER BY 
                    u.last_name ASC";
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
     * Finds tasks by their work status, optionally filtered by phase.
     *
     * This method retrieves a collection of tasks that match the specified work status.
     * Optionally, tasks can be filtered by a specific phase, identified by either an integer ID or a UUID.
     * The method supports pagination through the 'limit' and 'offset' options.
     *
     * @param WorkStatus $status The work status to filter tasks by.
     * @param int|UUID|null $phaseId (optional) The phase identifier. Can be an integer ID, a UUID, or null to include all phases.
     * @param array $options (optional) Query options:
     *      - limit: int (default 10) Maximum number of tasks to return.
     *      - offset: int (default 0) Number of tasks to skip before starting to collect the result set.
     *
     * @throws InvalidArgumentException If an invalid phase ID is provided.
     * @throws Exception If an error occurs during the query.
     *
     * @return TaskContainer|null A container of tasks matching the criteria, or null if none found.
     */
    public static function findByStatus(
        WorkStatus $status,
        int|UUID|null $phaseId = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?TaskContainer
    {
        if ($phaseId && is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID provided.');
        }

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $whereClause = 'pt.status = :status';
            $params = [':status' => $status->value];

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pp.id = :phaseId'
                    : ' AND pp.public_id = :phaseId';
                $params[':phaseId'] = is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId);
            }

            return self::find($whereClause, $params, $paramOptions);
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
        int|UUID|null $phaseId = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?TaskContainer
    {
        if ($phaseId && is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID provided.');
        }

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $whereClause = 'pt.priority = :priority';
            $params = [':priority' => $priority->value];

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pp.id = :phaseId'
                    : ' AND pp.public_id = :phaseId';
                $params[':phaseId'] = is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId);
            }

            return self::find($whereClause, $params, $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds and returns the count of tasks grouped by status for a specific phase.
     *
     * This method queries the database to retrieve task counts grouped by their status
     * for a given phase ID. It validates the input and handles database exceptions.
     *
     * @param int $phaseId The unique identifier of the phase to query tasks for
     * 
     * @return array|null Array of status counts where each element contains:
     *      - status: string The status of the tasks
     *      - count: int The number of tasks with that status
     *      Returns null if no tasks are found for the phase
     * 
     * @throws InvalidArgumentException If the provided phase ID is less than 1
     * @throws DatabaseException If a database error occurs during query execution
     */
    public static function findStatusCountByPhaseId(int $phaseId): ?array
    {
        if ($phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID provided.');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    pt.status AS task_status,
                    COUNT(*) AS task_count
                FROM 
                    `phase_task` AS pt
                WHERE 
                    pt.phase_id = :phaseId
                GROUP BY 
                    pt.status";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':phaseId' => $phaseId]);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            $statusCounts = [];
            foreach ($results as $row) {
                $statusCounts[$row['task_status']] = (int)$row['task_count'];
            }

            return $statusCounts;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds and returns the count of tasks grouped by priority for a specific phase.
     *
     * This method retrieves task distribution statistics by querying the database
     * for all tasks associated with the given phase ID and groups them by their
     * priority level. The results include the priority value and the number of
     * tasks for each priority.
     *
     * @param int $phaseId The unique identifier of the phase to query
     * 
     * @return array|null Array of associative arrays containing priority counts, or null if no tasks found.
     *      Each array element contains:
     *      - priority: string The priority level of the tasks
     *      - count: int The number of tasks with this priority
     * 
     * @throws InvalidArgumentException If the provided phase ID is less than 1
     * @throws DatabaseException If a database error occurs during query execution
     */
    public static function findPriorityCountByPhaseId(int $phaseId): ?array
    {
        if ($phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase ID provided.');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    pt.priority AS task_priority,
                    COUNT(*) AS task_count
                FROM 
                    `phase_task` AS pt
                WHERE 
                    pt.phase_id = :phaseId
                GROUP BY 
                    pt.priority";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':phaseId' => $phaseId]);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            $priorityCounts = [];
            foreach ($results as $row) {
                $priorityCounts[$row['task_priority']] = (int)$row['task_count'];
            }

            return $priorityCounts;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }   

    /**
     * Finds and returns the Project that owns a given Task (through its Phase).
     *
     * This method retrieves the project associated with the specified task ID (either integer or UUID).
     * It joins through phaseTask -> projectPhase -> project tables to fetch project details and its manager's information.
     * Returns a partial Project instance with the manager as a partial User instance, or null if not found.
     *
     * @param int|UUID $taskId The ID or public UUID of the task whose owning project is to be found.
     *
     * @throws InvalidArgumentException If the provided task ID is invalid.
     * @throws DatabaseException If a database error occurs during the query.
     *
     * @return Project|null The owning Project instance, or null if no project is found for the given task.
     */
    public static function findOwningProject(int|UUID $taskId): ?Project
    {
        if (is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID provided.');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    p.*,
                    u.id AS id,
                    u.public_id AS public_id,
                    u.first_name AS first_name,
                    u.middle_name AS middle_name,
                    u.last_name AS last_name,
                    u.gender AS gender,
                    u.email AS email,
                    u.profile_link AS profile_link 
                FROM 
                    `project` AS p
                INNER JOIN
                    `user` AS u 
                ON 
                    p.manager_id = u.id
                INNER JOIN 
                    `project_phase` AS pp
                ON
                    p.id = pp.project_id
                INNER JOIN
                    `phase_task` AS pt
                ON
                    pp.id = pt.phase_id
                WHERE 
                    " . (is_int($taskId) 
                        ? 'pt.id = :taskId' 
                        : 'pt.public_id = :taskId') . "
                LIMIT 1";
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
                'publicId'                  => $result['public_id'],
                'name'                      => $result['name'],
                'description'               => $result['description'],
                'budget'                    => $result['budget'],
                'status'                    => $result['status'],
                'startDateTime'             => new DateTime($result['start_date_time']),
                'completionDateTime'        => new DateTime($result['completion_date_time']),
                'actualCompletionDateTime'  => new DateTime($result['actual_completion_date_time']),
                'createdAt'                 => new DateTime($result['created_at']),
                'manager'                   => User::createPartial([
                    'id'           => $result['id'],
                    'publicId'     => $result['public_id'],
                    'firstName'    => $result['first_name'],
                    'middleName'   => $result['middle_name'],
                    'lastName'     => $result['last_name'],
                    'gender'       => $result['gender'],
                    'email'        => $result['email'],
                    'profileLink'  => $result['profile_link'],
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
            $paramOptions = [
                'offset'    => $offset,
                'limit'     => $limit,
            ];
            return self::find('', [], $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Inserts a new Task into the database and assigns workers to it.
     *
     * This method performs the following operations within a transaction:
     * - Validates that the provided object is an instance of Task.
     * - Generates or retrieves the public_id for the task.
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
     *
     * @return Task The Task object with updated id and public_id after successful insertion.
     */
    public static function create(mixed $task): mixed
    {
        if (!($task instanceof Task)) {
            throw new InvalidArgumentException('Expected instance of Task');
        }

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $phaseId            = $task->getAdditionalInfo('phaseId');
            $taskPublicId       = $task->getPublicId() ?? UUID::get();
            $taskName           = trimOrNull($task->getName());
            $taskDescription    = trimOrNull($task->getDescription());
            $taskPriority       = $task->getPriority()->value;
            $taskStatus         = $task->getStatus()->value;
            $taskWorkers        = $task->getWorkers()->getItems();
            $taskStartDateTime  = formatDateTime($task->getStartDateTime());
            $completionDateTime = formatDateTime($task->getCompletionDateTime());

            $taskQuery = "
                INSERT INTO `phase_task` (
                    public_id, 
                    phase_id,
                    name, 
                    description, 
                    priority, 
                    status, 
                    start_date_time, 
                    completion_date_time
                ) VALUES (
                    :publicId, 
                    " . (is_int($phaseId) 
                        ? ':phaseId,' 
                        : '(SELECT id FROM `project_phase` WHERE public_id = :phaseId),') . "
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
                ':publicId'             => UUID::toBinary($taskPublicId),
                ':phaseId'              => is_int($phaseId) ? $phaseId : UUID::toBinary($phaseId),
                ':name'                 => $taskName,
                ':description'          => $taskDescription,
                ':priority'             => $taskPriority,
                ':status'               => $taskStatus,
                ':startDateTime'        => $taskStartDateTime,
                ':completionDateTime'   => $completionDateTime,
            ]);
            $taskId = (int)$instance->connection->lastInsertId();

            if ($taskWorkers && count($taskWorkers) > 0) {
                $taskWorkerQuery = "
                    INSERT INTO `phase_task_worker` (
                        task_id,
                        worker_id,
                        status
                    ) SELECT :taskId, id, :status
                    FROM `user`
                    WHERE public_id = :workerId";
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
     * @throws InvalidArgumentException If the provided task ID is invalid.
     * @throws DatabaseException If a database error occurs during the update process.
     * @return bool True on successful update, false otherwise.
     */
    public static function save(array $data): bool
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $updateFields = [];
            $params = [];
            if (isset($data['id'])) {
                if (!is_int($data['id']) || $data['id'] < 1) {
                    throw new InvalidArgumentException('Invalid task ID.');
                }

                $params[':id'] = $data['id'];
            } elseif (isset($data['publicId'])) {
                $params[':publicId'] = UUID::toBinary($data['publicId']);
            } else {
                throw new InvalidArgumentException('Task ID or Public ID is required.');
            }

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
                $updateFields[] = 'start_date_time = :startDateTime';
                $params[':startDateTime'] = formatDateTime($data['startDateTime']);
            }

            if (isset($data['completionDateTime'])) {
                $updateFields[] = 'completion_date_time = :completionDateTime';
                $params[':completionDateTime'] = formatDateTime($data['completionDateTime']);
            }

            if (isset($data['actualCompletionDateTime'])) {
                $updateFields[] = 'actual_completion_date_time = :actualCompletionDateTime';
                $params[':actualCompletionDateTime'] = $data['actualCompletionDateTime'] !== null 
                    ? formatDateTime($data['actualCompletionDateTime']) 
                    : null;
            }

            if (!empty($updateFields)) {
                $phaseQuery = "UPDATE `phase_task` SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $statement = $instance->connection->prepare($phaseQuery);
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
     * Deletes a phase entity.
     *
     * This method is currently not implemented as there is no use case for deleting a phase.
     * Always returns false.
     * 
     * @param mixed $data Data that would be used to delete a phase (unused)
     *
     * @return bool Always returns false to indicate deletion is not supported.
     */
    public static function delete(mixed $data): bool
    {
        // Not implemented (No use case)
        return false;
    }
}