<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use Exception;
use InvalidArgumentException;
use PDOException;

class TaskWorkerModel extends Model
{

    /**
     * Finds and retrieves worker data based on specified conditions.
     *
     * This method executes a complex SQL query to fetch worker information, including:
     * - Worker personal details (id, publicId, firstName, middleName, lastName, bio, gender, email, contactNumber, profileLink)
     * - Worker status in phase tasks
     * - Aggregated job titles (as an array)
     * - Total number of tasks assigned to the worker
     * - Number of completed tasks by the worker
     *
     * The query joins multiple tables: user, phaseTaskWorker, phaseTask, projectPhase, project, and userJobTitle.
     * It supports dynamic WHERE clauses, query parameters, and additional query options.
     * Results are returned as a WorkerContainer containing Worker instances with partial data.
     *
     * @param string $whereClause Optional SQL WHERE clause to filter results.
     * @param array $params Parameters to bind to the SQL query.
     * @param array $options Additional options to modify the query (e.g., sorting, limiting).
     * 
     * @return WorkerContainer|null Container of Worker instances if data is found, or null if no data matches.
     * 
     * @throws DatabaseException If a database error occurs during query execution.
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?WorkerContainer
    {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'u.id',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'u.last_name ASC',
        ];

        $instance = new self();
        try {
            $queryString = "
                SELECT 
                    u.id,
                    u.public_id,
                    u.first_name,
                    u.middle_name,
                    u.last_name,
                    u.bio,
                    u.gender,
                    u.email,
                    u.contact_number,
                    u.profile_link,
                    ptw.status,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(ujt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        WHERE 
                            ptw.worker_id = u.id
                    ) AS total_tasks,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        INNER JOIN 
                            `phase_task` AS pt 
                        ON 
                            ptw.task_id = pt.id
                        WHERE 
                            ptw.worker_id = u.id AND pt.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            ptw.status != '" . WorkerStatus::TERMINATED->value . "'
                    ) AS completed_tasks
                FROM
                    `user` AS u
                INNER JOIN
                    `phase_task_worker` AS ptw 
                ON 
                    u.id = ptw.worker_id
                INNER JOIN
                    `phase_task` AS pt
                ON
                    pt.id = ptw.task_id
                INNER JOIN
                    `project_phase` AS pp
                ON
                    pp.id = pt.phase_id
                INNER JOIN
                    `project` AS p
                ON
                    p.id = pp.project_id
                LEFT JOIN
                    `user_job_title` AS ujt
                ON 
                    u.id = ujt.user_id
            ";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause),
                $paramOptions
            );

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['job_titles'] = explode(',', $row['job_titles']);
                $row['additionalInfo'] = [
                    'totalTasks'        => (int) $row['total_tasks'],
                    'completedTasks'    => (int) $row['completed_tasks']
                ];
                $workers->add(Worker::createPartial($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * Finds a Worker instance by its identifier and optional related identifiers.
     *
     * This method retrieves a Worker based on the provided workerId, and can further filter by taskId, phaseId, and projectId.
     * It supports both integer and UUID formats for all identifiers, converting UUIDs to binary as needed for database queries.
     * The method validates all provided IDs, ensuring they are positive integers or valid UUIDs.
     * The search is performed using dynamically constructed SQL WHERE clauses based on the types and presence of the parameters.
     *
     * @param int|UUID $workerId The unique identifier of the worker (integer or UUID).
     * @param int|UUID|null $taskId (optional) The unique identifier of the task (integer or UUID).
     * @param int|UUID|null $phaseId (optional) The unique identifier of the phase (integer or UUID).
     * @param int|UUID|null $projectId (optional) The unique identifier of the project (integer or UUID).
     *
     * @throws InvalidArgumentException If any provided ID is an invalid integer (less than 1).
     * @throws Exception If an error occurs during the query execution.
     *
     * @return Worker|null The found Worker instance, or null if no matching worker is found.
     */
    public static function findById(
        int|UUID $workerId, 
        int|UUID|null $taskId = null,  
        int|UUID|null $phaseId = null, 
        int|UUID|null $projectId = null
    ): ?Worker {
        if (is_int($workerId) && $workerId < 1) {
            throw new InvalidArgumentException('Invalid worker_id provided.');
        }

        if (is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phase_id provided.');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project_id provided.');
        }

        if ($taskId && is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task_id provided.');
        }

        try {
            $whereClause = is_int($workerId)
                ? 'ptw.worker_id = :workerId'
                : 'u.public_id = :workerId';
            $params = [':workerId' => is_int($workerId) ? $workerId : UUID::toBinary($workerId)];

            if ($taskId) {
                $whereClause .= is_int($taskId)
                    ? ' AND ptw.task_id = :taskId'
                    : ' AND pt.public_id = :taskId';
                $params[':taskId'] = is_int($taskId) ? $taskId : UUID::toBinary($taskId);
            }

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pt.phase_id = :phaseId'
                    : ' AND pt.phase_id IN (SELECT id FROM `project_phase` WHERE public_id = :phaseId)';
                $params[':phaseId'] = is_int($phaseId) ? $phaseId : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND pp.project_id = :projectId'
                    : ' AND pp.project_id IN (SELECT id FROM `project` WHERE public_id = :projectId)';
                $params[':projectId'] = is_int($projectId) ? $projectId : UUID::toBinary($projectId);
            }

            $paramOptions = ['limit' => 1];

            $worker = self::find($whereClause, $params, $paramOptions);
            return $worker->first() ?? null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds multiple workers by their IDs, optionally filtered by task, phase, or project.
     *
     * This method retrieves worker records based on an array of worker IDs, which can be either integers or UUIDs.
     * It supports additional filtering by task ID, phase ID, and project ID, each of which can be an integer or UUID.
     * The method dynamically builds the SQL WHERE clause and parameter bindings according to the types of provided IDs.
     * Throws an InvalidArgumentException if the worker IDs array is empty or if any provided integer ID is invalid.
     *
     * @param array $workerIds Array of worker IDs (int|UUID) to search for. Must not be empty.
     * @param int|UUID|null $taskId Optional task ID to filter by (int or UUID).
     * @param int|UUID|null $phaseId Optional phase ID to filter by (int or UUID).
     * @param int|UUID|null $projectId Optional project ID to filter by (int or UUID).
     *
     * @throws InvalidArgumentException If $workerIds is empty or if any provided integer ID is invalid.
     * @throws Exception If an error occurs during query execution.
     *
     * @return WorkerContainer|null Container of found workers, or null if none found.
     */
    public static function findMultipleById(
        array $workerIds,
        int|UUID|null $taskId = null,
        int|UUID|null $phaseId = null,
        int|UUID|null $projectId = null
    ): ?WorkerContainer {
        if (empty($workerIds)) {
            throw new InvalidArgumentException('Worker IDs array cannot be empty.');
        }

        if ($taskId && is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID provided.');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            // Determine if workerIds are integers or UUIDs based on first element
            $firstWorkerId = $workerIds[0];
            $useIntId = is_int($firstWorkerId);

            // Build WHERE clause for multiple worker IDs
            $workerIdPlaceholders = [];
            $params = [];
            
            foreach ($workerIds as $index => $workerId) {
                $placeholder = ":workerId$index";
                $workerIdPlaceholders[] = $placeholder;
                $params[$placeholder] = ($workerId instanceof UUID) 
                    ? UUID::toBinary($workerId)
                    : $workerId;
            }

            $workerIdColumn = $useIntId ? "ptw.worker_id" : "u.public_id";
            $whereClause = "$workerIdColumn IN (" . implode(', ', $workerIdPlaceholders) . ")";

            if ($taskId) {
                $whereClause .= is_int($taskId)
                    ? ' AND ptw.task_id = :taskId'
                    : ' AND pt.public_id = :taskId';
                $params[':taskId'] = is_int($taskId) ? $taskId : UUID::toBinary($taskId);
            }

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pt.phase_id = :phaseId'
                    : ' AND pt.phase_id IN (SELECT id FROM `project_phase` WHERE public_id = :phaseId)';
                $params[':phaseId'] = is_int($phaseId) ? $phaseId : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND pp.project_id = :projectId'
                    : ' AND pp.project_id IN (SELECT id FROM `project` WHERE public_id = :projectId)';
                $params[':projectId'] = is_int($projectId) ? $projectId : UUID::toBinary($projectId);
            }

            return self::find($whereClause, $params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds a WorkerContainer instance by the given task ID.
     *
     * This method supports searching by either an integer task ID or a UUID. It constructs
     * the appropriate SQL WHERE clause and parameter binding based on the type of $taskId:
     * - If $taskId is an integer, it uses the internal numeric task ID.
     * - If $taskId is a UUID, it uses the public_id field and converts the UUID to binary.
     *
     * Throws an InvalidArgumentException if an invalid integer task ID is provided.
     * Any exceptions during the find operation are rethrown.
     *
     * @param int|UUID $taskId The task identifier, either as an integer (internal ID) or UUID (public ID).
     *
     * @return WorkerContainer|null The found WorkerContainer instance, or null if not found.
     *
     * @throws InvalidArgumentException If an invalid integer task ID is provided.
     * @throws Exception If an error occurs during the find operation.
     */
    public static function findByTaskId(int|UUID $taskId): ?WorkerContainer
    {
        if (is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID provided.');
        }

        try {
            $whereClause = is_int($taskId)
                ? 'ptw.task_id = :taskId'
                : 'pt.public_id = :taskId';
            $params = [':taskId' => is_int($taskId) ? $taskId : UUID::toBinary($taskId)];

            return self::find($whereClause, $params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Searches for workers based on various criteria such as key, task, project, and status.
     *
     * This method supports searching for unassigned, assigned, or terminated workers within a project or task context.
     * It can filter by full-text search, project, task, worker status, and supports pagination and exclusion of terminated workers.
     * The returned result is a WorkerContainer containing partial Worker objects.
     *
     * @param string|null $key Optional full-text search key for worker's name, bio, or email.
     * @param int|UUID|null $taskId Optional task ID or public UUID to filter workers by a specific task.
     * @param int|UUID|null $projectId Optional project ID or public UUID to filter workers by a specific project.
     * @param WorkerStatus|null $status Optional status to filter workers (e.g., UNASSIGNED, ASSIGNED, TERMINATED).
     * @param array $options Optional associative array of search options:
     *      - excludeTaskTerminated: bool Whether to exclude workers terminated from the specified task (default: false).
     *      - limit: int Maximum number of results to return (default: 10).
     *      - offset: int Number of results to skip for pagination (default: 0).
     *
     * @throws InvalidArgumentException If project_id is not provided when searching for unassigned workers.
     * @throws DatabaseException If a database error occurs during the search.
     *
     * @return WorkerContainer|null A container of Worker objects matching the search criteria, or null if no workers found.
     */
    public static function search(
        string|null $key = '',
        int|UUID|null $taskId = null,
        int|UUID|null $phaseId = null,
        int|UUID|null $projectId = null,
        WorkerStatus|null $status = null,
        array $options = [
            'excludeTaskTerminated' => false,
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?WorkerContainer {
        $paramOptions = [
            'excludeTaskTerminated' => $options['excludeTaskTerminated'] ?? false,
            'limit'                 => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'                => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $instance = new self();

            $where = [];
            $params = [];

            // Base query structure depends on status
            if ($status === WorkerStatus::UNASSIGNED) {
                // For unassigned: start with project workers, then filter out those on tasks
                if (!$projectId) {
                    throw new InvalidArgumentException('Project ID is required.');
                }

                $query = "
                    SELECT 
                        u.id,
                        u.public_id,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        u.birth_date,
                        u.gender,
                        u.email,
                        u.contact_number,
                        u.profile_link,
                        u.created_at,
                        u.confirmed_at,
                        u.deleted_at,
                        GROUP_CONCAT(DISTINCT ujt.title) AS job_titles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `project_worker` AS pw
                    ON
                        u.id = pw.worker_id
                    AND 
                        pw.project_id = " . (is_int($projectId) 
                            ? ":projectIdJoin" 
                            : "(SELECT id FROM `project` WHERE public_id = :projectIdJoin)") . "
                    AND 
                        pw.status = :assignedProjectStatus
                    LEFT JOIN
                        `user_job_title` AS ujt
                    ON
                        u.id = ujt.user_id
                ";

                $params[':projectIdJoin'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
                $params[':assignedProjectStatus'] = WorkerStatus::ASSIGNED->value;

                if ($taskId && ($options['excludeTaskTerminated'])) {
                    // Exclude workers terminated from this specific task
                    $where[] = "NOT EXISTS (
                        SELECT 1
                        FROM 
                            `phase_task_worker` AS ptw3
                        WHERE 
                            ptw3.worker_id = u.id
                        AND 
                            ptw3.task_id = " . (is_int($taskId) 
                            ? ":taskIdTermCheck" 
                            : "(SELECT id FROM `phase_task` WHERE public_id = :taskIdTermCheck)") . "
                        AND 
                            ptw3.status = :terminatedStatus
                    )";
                    $params[':terminatedStatus'] = WorkerStatus::TERMINATED->value;
                    $params[':taskIdTermCheck'] = is_int($taskId)
                        ? $taskId
                        : UUID::toBinary($taskId);
                }
            } else {
                // For assigned/terminated: query task workers directly
                $query = "
                    SELECT 
                        u.id,
                        u.public_id,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        u.birth_date,
                        u.gender,
                        u.email,
                        u.contact_number,
                        ptw.status,
                        u.created_at,
                        u.confirmed_at,
                        u.deleted_at,
                        u.profile_link,
                        GROUP_CONCAT(DISTINCT ujt.title) AS job_titles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `phase_task_worker` AS ptw
                    ON
                        u.id = ptw.worker_id
                    INNER JOIN
                        `phase_task` AS pt
                    ON
                        pt.id = ptw.task_id
                    INNER JOIN
                        `project_phase` AS pp
                    ON
                        pp.id = pt.phase_id
                    INNER JOIN
                        `project` AS p
                    ON
                        p.id = pp.project_id
                    LEFT JOIN
                        `user_job_title` AS ujt
                    ON
                        u.id = ujt.user_id
                ";

                if ($taskId) {
                    $where[] = is_int($taskId)
                        ? "pt.id = :taskId"
                        : "pt.public_id = :taskId";
                    $params[':taskId'] = is_int($taskId)
                        ? $taskId
                        : UUID::toBinary($taskId);
                }

                if ($phaseId) {
                    $where[] = is_int($phaseId)
                        ? "pt.phase_id = :phaseId"
                        : "pt.phase_id = (SELECT id FROM `project_phase` WHERE public_id = :phaseId)";
                    $params[':phaseId'] = is_int($phaseId)
                        ? $phaseId
                        : UUID::toBinary($phaseId);
                }

                if ($projectId) {
                    $where[] = is_int($projectId)
                        ? "p.id = :projectId"
                        : "p.id = (SELECT id FROM `project` WHERE public_id = :projectId)";
                    $params[':projectId'] = is_int($projectId)
                        ? $projectId
                        : UUID::toBinary($projectId);
                }

                if ($status) {
                    $where[] = "ptw.status = :status";
                    $params[':status'] = $status->value;
                }
            }

            // Full-text search (applies to both queries)
            if (trimOrNull($key))  {
                $where[] = "MATCH(u.first_name, u.middle_name, u.last_name, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Role filter (applies to both queries)
            $where[] = "u.role = :role";
            $params[':role'] = Role::WORKER->value;

            $where[] = "u.confirmed_at IS NOT NULL AND u.deleted_at IS NULL";

            if (!empty($where)) {
                $query .= " WHERE " . implode(' AND ', $where);
            }

            $query .= " 
                GROUP BY 
                    u.id 
                ORDER BY 
                    u.last_name ASC
                LIMIT " 
                    . intval($paramOptions['limit']) . 
                " OFFSET " 
                    . intval($paramOptions['offset']);

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['job_titles'] = $row['job_titles'] 
                    ? explode(',', $row['job_titles']) 
                    : [];
                $workers->add(Worker::createPartial($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves a paginated list of all worker containers.
     *
     * This method fetches worker records from the data source with optional pagination and ordering.
     * It validates the provided offset and limit parameters before querying.
     *
     * @param int $offset The starting index for the records to retrieve (must be >= 0).
     * @param int $limit The maximum number of records to retrieve (must be >= 1).
     *
     * @throws InvalidArgumentException If the offset is negative or the limit is less than 1.
     * @throws Exception If an error occurs during the retrieval process.
     *
     * @return WorkerContainer|null A container of worker records, or null if none found.
     */
    public static function all(int $offset = 0, int $limit = 10): ?WorkerContainer
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
     * Determines whether a given user currently works on a specific task (optionally within a project).
     *
     * This method accepts either integer IDs or UUID objects for task, user and project identifiers.
     * It validates numeric IDs (must be >= 1) and converts UUID objects to their binary representation
     * for use in the prepared SQL query. If a project_id is provided, additional JOINs are added to
     * restrict the check to the specified project. The query checks the phaseTaskWorker record and
     * ensures the worker's assignment status is not WorkerStatus::TERMINATED.
     *
     * Behavior details:
     * - Validates that integer IDs are >= 1; if not, throws InvalidArgumentException.
     * - Converts UUID instances to binary via UUID::toBinary() before binding to the statement.
     * - Builds a query joining phaseTaskWorker -> phaseTask -> user and optionally projectPhase -> project.
     * - Uses prepared statements and parameter binding to avoid SQL injection.
     * - Treats "terminated" assignments (ptw.status == WorkerStatus::TERMINATED) as non-working.
     *
     * @param int|UUID $taskId    Task identifier (numeric primary ID) or public UUID.
     * @param int|UUID $userId    User identifier (numeric primary ID) or public UUID.
     * @param int|UUID|null $projectId Optional project identifier (numeric primary ID) or public UUID; pass null to ignore project constraint.
     *
     * @return bool True if the user has a non-terminated assignment on the task (and within the project if provided); false otherwise.
     *
     * @throws InvalidArgumentException If any provided integer ID is less than 1.
     * @throws DatabaseException If a database error occurs while executing the query (wraps PDOException).
     */
    public static function worksOn(int|UUID $taskId, int|UUID $userId, int|UUID|null $projectId = null): bool
    {
        if (is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID provided.');
        }

        if (is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided.');
        }

        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            $instance = new self();

            $params = [
                ':taskId'        => ($taskId instanceof UUID)
                    ? UUID::toBinary($taskId)
                    : $taskId,
                ':userId'           => ($userId instanceof UUID)
                    ? UUID::toBinary($userId)
                    : $userId,
                ':terminatedStatus' => WorkerStatus::TERMINATED->value
            ];

            $projectJoin = '';
            if ($projectId) {
                $projectJoin = "
                    INNER JOIN
                        `project_phase` AS pp
                    ON
                        pp.id = pt.phase_id
                    INNER JOIN
                        `project` AS p
                    ON
                        p.id = pp.project_id
                ";
                $params[':projectId'] = ($projectId instanceof UUID)
                    ? UUID::toBinary($projectId)
                    : $projectId;
            }

            $query = "
                SELECT *
                FROM 
                    `phase_task_worker` AS ptw
                INNER JOIN
                    `phase_task` AS pt
                ON
                    pt.id = ptw.task_id
                INNER JOIN
                    `user` AS u
                ON
                    u.id = ptw.worker_id
                " . $projectJoin . "
                WHERE 
                    " . (is_int($taskId) ? 'pt.id' : 'pt.public_id') . " = :taskId
                AND 
                    " . (is_int($userId) ? 'u.id' : 'u.public_id') . " = :userId
                    " . ($projectId ? "AND " . (is_int($projectId) ? 'p.id' : 'p.public_id') . " = :projectId" : '') . "
                AND 
                    ptw.status != :terminatedStatus
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            return $instance->hasData($statement->fetchAll());
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Creates a TaskWorker instance from the provided data.
     *
     * This method is intended to instantiate a TaskWorker object from an array or object of data.
     * Currently, this method is not implemented as there is no use case for creating TaskWorker instances in this way.
     *
     * @param mixed $data Data used to create a TaskWorker instance. Expected to be an associative array or object with relevant fields.
     * 
     * @return mixed Returns null as this method is not implemented.
     */
    public static function create(mixed $data): mixed
    {
        // Not implemented (No use case)
        return null;
    }

    /**
     * Creates multiple task-worker assignments for a given task.
     *
     * This method inserts multiple worker assignments into the `projectTaskWorker` table for the specified task.
     * It uses a transaction to ensure all assignments are created atomically. Each worker is referenced by their
     * public UUID, which is converted to binary if necessary. The task is also referenced by its public UUID.
     * The status for each assignment is set to WorkerStatus::ASSIGNED.
     *
     * The query uses a CROSS JOIN with WHERE filters to match the task and worker by their public UUIDs,
     * then extracts their internal IDs for insertion. If a duplicate taskId-workerId pair exists,
     * the status is updated to the new value (upsert behavior).
     *
     * @param int|UUID $taskId The public UUID or integer ID of the task to assign workers to.
     * @param array $data Array of worker public UUIDs or binary IDs to be assigned to the task.
     *
     * @throws InvalidArgumentException If the data array is empty.
     * @throws DatabaseException If a database error occurs during the transaction.
     * 
     * @return void
     */
    public static function createMultiple(int|UUID $taskId, array $data): void
    {
        if (is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID provided.');
        }

        if (empty($data)) {
            throw new InvalidArgumentException('No data provided.');
        }

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $isTaskInt = is_int($taskId);
            $isWorkerInt = is_int($data[0]);
            
            $insertQuery = "
                INSERT INTO 
                    `phase_task_worker` (task_id, worker_id, status)
                VALUES (
                    " . ($isTaskInt 
                        ? ":task_id" 
                        : "(SELECT id FROM `phase_task` WHERE public_id = :taskId)") . ",
                    " . ($isWorkerInt 
                        ? ":worker_id" 
                        : "(SELECT id FROM `user` WHERE public_id = :workerId)") . ",
                    :status
                )
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status)";
            
            $statement = $instance->connection->prepare($insertQuery);
            
            $taskIdParam = ($taskId instanceof UUID) ? UUID::toBinary($taskId) : $taskId;
            
            foreach ($data as $id) {    
                $statement->execute([
                    ':taskId'   => $taskIdParam,
                    ':workerId' => ($id instanceof UUID) ? UUID::toBinary($id) : $id,
                    ':status'   => WorkerStatus::ASSIGNED->value
                ]);
            }

            $instance->connection->commit();
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }

	public static function save(array $data): bool
	{
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $updateFields = [];
            $params = [];

            // Determine identifier clause: prefer numeric/internal id when provided
            if (isset($data['id'])) {
                $where = 'id = :id';
                $params[':id'] = $data['id'];
            } else {
                // Require task_id and worker_id when id is not provided
                if (is_int($data['taskId']) && $data['taskId'] < 1) {
                    throw new InvalidArgumentException('Invalid task ID provided.');
                }

                if (is_int($data['workerId']) && $data['workerId'] < 1) {
                    throw new InvalidArgumentException('Invalid worker ID provided.');
                }

                $whereParts = [];
                // task_id may be int or UUID
                if ($data['taskId'] instanceof UUID) {
                    $whereParts[] = 'task_id = (SELECT id FROM `phase_task` WHERE public_id = :taskPublicId)';
                    $params[':taskPublicId'] = UUID::toBinary($data['taskId']);
                } else {
                    $whereParts[] = 'task_id = :taskId';
                    $params[':taskId'] = $data['taskId'];
                }

                // worker_id may be int or UUID
                if ($data['workerId'] instanceof UUID) {
                    $whereParts[] = 'worker_id = (SELECT id FROM `user` WHERE public_id = :workerPublicId)';
                    $params[':workerPublicId'] = UUID::toBinary($data['workerId']);
                } else {
                    $whereParts[] = 'worker_id = :workerId';
                    $params[':workerId'] = $data['workerId'];
                }

                $where = implode(' AND ', $whereParts);
            }

            // Build update fields
            if (isset($data['status'])) {
                $updateFields[] = 'status = :status';
                $params[':status'] = ($data['status'] instanceof WorkerStatus)
                    ? $data['status']->value
                    : $data['status'];
            }

            // Nothing to update
            if (empty($updateFields)) {
                $instance->connection->commit();
                return true;
            }

            $query = 'UPDATE `phase_task_worker` SET ' . implode(', ', $updateFields) . ' WHERE ' . $where;
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);

            $instance->connection->commit();
            return true;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
	}

    /**
     * Deletes a task-worker association from the phaseTaskWorker table.
     *
     * This method accepts a data array describing which association to delete and
     * supports multiple identifier formats for both task and worker:
     * - Accepts internal integer IDs for direct deletion.
     * - Accepts public identifiers (string or binary) or UUID objects; UUID objects
     *   are converted to binary via UUID::toBinary().
     * - When non-integer identifiers are provided, the query resolves them to
     *   internal IDs using subqueries against `phaseTask.public_id` and `user.public_id`.
     *
     * Validation performed:
     * - Ensures 'taskId' and 'workerId' are present.
     * - If provided as integers, ensures they are greater than zero.
     *
     * The deletion is performed using a prepared statement and bound parameters.
     *
     * @param array $data Associative array containing identifiers with following keys:
     *      - taskId: int|string|UUID|binary Task identifier to remove association for.
     *          - int: internal task id (must be > 0)
     *          - string|binary: public_id of the task (resolved to internal id via subquery)
     *          - UUID: UUID object which will be converted to binary
     *      - workerId: int|string|UUID|binary Worker identifier to remove association for.
     *          - int: internal worker id (must be > 0)
     *          - string|binary: public_id of the user (resolved to internal id via subquery)
     *          - UUID: UUID object which will be converted to binary
     *
     * @return bool True on successful deletion.
     *
     * @throws InvalidArgumentException If required keys are missing or integer IDs are invalid.
     * @throws DatabaseException If a database error occurs during the operation (wraps PDOException).
     */
    public static function delete(mixed $data): bool
    {
        if (!isset($data['taskId'])) {
            throw new InvalidArgumentException('Task ID is required.');
        }

        if (is_int($data['taskId']) && $data['taskId'] < 1) {
            throw new InvalidArgumentException('Invalid task ID provided.');
        }

        if (!isset($data['workerId'])) {
            throw new InvalidArgumentException('Worker ID is required.');
        }

        if (is_int($data['workerId']) && $data['workerId'] < 1) {
            throw new InvalidArgumentException('Invalid worker ID provided.');
        }

        try {
            $query = "
                DELETE FROM
                    `phase_task_worker`
                WHERE 
                    task_id = " . (is_int($data['taskId']) ? ':taskId' : '(
                        SELECT 
                            id 
                        FROM 
                            `phase_task` 
                        WHERE 
                            public_id = :taskId) ') . "
                AND 
                    worker_id = " . (is_int($data['workerId']) ? ':workerId' : '(
                        SELECT 
                            id 
                        FROM 
                            `user` 
                        WHERE
                            public_id = :workerId)') . "
            ";

            $instance = new self();
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':taskId'    => ($data['taskId'] instanceof UUID)
                    ? UUID::toBinary($data['taskId'])
                    : $data['taskId'],
                ':workerId'     => ($data['workerId'] instanceof UUID)
                    ? UUID::toBinary($data['workerId'])
                    : $data['workerId'],
            ]);

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }
}