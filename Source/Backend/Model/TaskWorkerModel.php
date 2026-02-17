<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Entity\TaskWorker;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use App\Utility\TemporaryId;
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
    protected function find(
        string $whereClause = '', 
        array $params = [], 
        array $options = []
    ): WorkerContainer|null {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'u.id, pw.default_rate, r.unit_rate, tw.estimated_hour, tw.actual_hour, tw.status',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'u.last_name ASC',
        ];

        try {
            $queryString =
                "SELECT 
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
                    pw.default_rate,
                    r.unit_rate,
                    tw.estimated_hour,
                    tw.actual_hour,
                    tw.status,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(jt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `task_worker` AS tw
                        WHERE 
                            tw.worker_id = u.id
                    ) AS total_tasks,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `task_worker` AS tw
                        INNER JOIN 
                            `task` AS t 
                        ON 
                            tw.task_id = t.id
                        WHERE 
                            tw.worker_id = u.id AND t.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            tw.status != '" . WorkerStatus::TERMINATED->value . "'
                    ) AS completed_tasks
                FROM
                    `user` AS u
                INNER JOIN
                    `task_worker` AS tw 
                ON 
                    u.id = tw.worker_id
                INNER JOIN 	
                    `resource` AS r
                ON 
                    r.task_worker_id = tw.id
                INNER JOIN 
                    `project_worker` AS pw
                ON
                    pw.worker_id = u.id
                INNER JOIN
                    `task` AS t 
                ON
                    t.id = tw.task_id
                INNER JOIN
                    `phase` AS ph
                ON
                    ph.id = t.phase_id
                INNER JOIN
                    `project` AS p
                ON
                    p.id = ph.project_id
                LEFT JOIN
                    `job_title` AS jt
                ON 
                    u.id = jt.user_id";
            $query = $this->appendOptionsToFindQuery(
                $this->appendWhereClause($queryString, $whereClause),
                $paramOptions
            );

            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$this->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['job_titles'] = explode(',', $row['job_titles']);
                $row['additionalInfo'] = [
                    'totalTasks'        => (int) $row['total_tasks'],
                    'completedTasks'    => (int) $row['completed_tasks']
                ];
                $workers->add(TaskWorker::createPartial($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

        /**
     * Shared ID-based worker lookup helper.
     *
     * Supports both single and multiple worker identifiers, in either internal int IDs
     * or public UUIDs. Optional filters can be applied via options.
     *
     * @param int|UUID|array $workerIds A single worker id (int|UUID) or an array of ids.
     * @param array $options Supported keys: taskId, phaseId, projectId
     * @param array $queryOptions Options forwarded to find() (e.g., limit, offset, orderBy)
     */
    private function findByIdHelper(
        int|UUID|array $workerIds,
        array $options = [],
        array $queryOptions = []
    ): WorkerContainer|null {
        $ids = \is_array($workerIds) ? $workerIds : [$workerIds];
        if (empty($ids)) throw new InvalidArgumentException('Worker IDs array cannot be empty');

        $taskId = $options['taskId'] ?? null;
        if ($taskId !== null) {
            if (!\is_int($taskId) && !($taskId instanceof UUID))
                throw new InvalidArgumentException('Task ID must be an integer or UUID');
            if (\is_int($taskId) && $taskId < 1)
                throw new InvalidArgumentException('Invalid task_id provided');
        }

        $phaseId = $options['phaseId'] ?? null;
        if ($phaseId !== null) {
            if (!\is_int($phaseId) && !($phaseId instanceof UUID))
                throw new InvalidArgumentException('Phase ID must be an integer or UUID');
            if (\is_int($phaseId) && $phaseId < 1)
                throw new InvalidArgumentException('Invalid phase_id provided');
        }

        $projectId = $options['projectId'] ?? null;
        if ($projectId !== null) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');
            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException('Invalid project_id provided');
        }

        $allInt = true;
        $allUuid = true;
        foreach ($ids as $id) {
            if (\is_int($id)) {
                if ($id < 1) throw new InvalidArgumentException('Invalid worker_id provided');
                $allUuid = false;
            } elseif ($id instanceof UUID) {
                $allInt = false;
            } else {
                throw new InvalidArgumentException('Worker ID must be an integer or UUID');
            }
        }
        if (!$allInt && !$allUuid)
            throw new InvalidArgumentException('Worker IDs must be all integers or all UUIDs');

        $params = [];
        if (\count($ids) === 1) {
            $whereClause = $allInt
                ? 'tw.worker_id = :workerId'
                : 'u.public_id = :workerId';
            $params[':workerId'] = $allInt
                ? $ids[0]
                : UUID::toBinary($ids[0]);
        } else {
            $placeholders = [];
            foreach (array_values($ids) as $index => $id) {
                $placeholder = ":workerId$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $allInt
                    ? $id
                    : UUID::toBinary($id);
            }

            $workerIdColumn = $allInt ? 'tw.worker_id' : 'u.public_id';
            $whereClause = "$workerIdColumn IN (" . implode(', ', $placeholders) . ')';
        }

        if ($taskId) {
            $whereClause .= \is_int($taskId)
                ? ' AND tw.task_id = :taskId'
                : ' AND t.public_id = :taskId';
            $params[':taskId'] = \is_int($taskId) ? $taskId : UUID::toBinary($taskId);
        }

        if ($phaseId) {
            $whereClause .= \is_int($phaseId)
                ? ' AND t.phase_id = :phaseId'
                : ' AND t.phase_id IN (SELECT id FROM `phase` WHERE public_id = :phaseId)';
            $params[':phaseId'] = \is_int($phaseId) ? $phaseId : UUID::toBinary($phaseId);
        }

        if ($projectId) {
            $whereClause .= \is_int($projectId)
                ? ' AND ph.project_id = :projectId'
                : ' AND ph.project_id IN (SELECT id FROM `project` WHERE public_id = :projectId)';
            $params[':projectId'] = \is_int($projectId) ? $projectId : UUID::toBinary($projectId);
        }

        return self::find($whereClause, $params, $queryOptions);
    }

    /* ------------------------------------------------------------------------------------------------ */

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
     * @return TaskWorker|null The found TaskWorker instance, or null if no matching worker is found.
     */
    public function findById(
        int|UUID $workerId,
        array $options = [
            'taskId'    => null,
            'phaseId'   => null,
            'projectId' => null,
    ]): TaskWorker|null {
        try {
            $workers = $this->findByIdHelper($workerId, $options, ['limit' => 1]);
            return $workers?->first() ?? null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds multiple workers by their IDs, optionally filtered by task, phase, or project.
     *
     * @param array $workerIds Array of worker IDs (int|UUID) to search for. Must not be empty.
     * @param array $options Supported keys: taskId, phaseId, projectId
     *
     * @return WorkerContainer|null
     */
    public function findByIds(
        array $workerIds,
        array $options = [
            'taskId'    => null,
            'phaseId'   => null,
            'projectId' => null,
        ]
    ): WorkerContainer|null {
        try {
            return $this->findByIdHelper($workerIds, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds all workers associated with a specific task ID.
     *
     * This method retrieves all workers assigned to the given task, identified by either an integer ID or a UUID.
     * It constructs the appropriate WHERE clause based on the type of taskId provided and delegates the search to the find() method.
     *
     * @param int|UUID $taskId The unique identifier of the task (integer or UUID).
     *
     * @return WorkerContainer|null A container of Worker instances if found, or null if no workers are associated with the task.
     *
     * @throws Exception If an error occurs during the query execution.
     */
    public function findByTaskId(int|UUID $taskId): WorkerContainer|null
    {
        $whereClause = \is_int($taskId)
            ? 'tw.task_id = :taskId'
            : 't.public_id = :taskId';
        $params = [':taskId' => \is_int($taskId)
            ? $taskId
            : UUID::toBinary($taskId)];

        return self::find($whereClause, $params);
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
    public function search(
        string|null $key = '',
        array $options = [
            'taskId'                => null,
            'phaseId'               => null,
            'projectId'             => null,
            'status'                => null,
            'excludeTaskTerminated' => false,

            'limit'                 => 10,
            'offset'                => 0,
        ]
    ): WorkerContainer|null {
        $taskId = $options['taskId'] ?? null;
        if ($taskId) {
            if (!\is_int($taskId) && !($taskId instanceof UUID))
                throw new InvalidArgumentException('Task ID must be an integer or UUID');
            if (\is_int($taskId) && $taskId < 1)
                throw new InvalidArgumentException('Invalid task ID provided');
        }

        $phaseId = $options['phaseId'] ?? null;
        if ($phaseId) {
            if (!\is_int($phaseId) && !($phaseId instanceof UUID))
                throw new InvalidArgumentException('Phase ID must be an integer or UUID');
            if (\is_int($phaseId) && $phaseId < 1)
                throw new InvalidArgumentException('Invalid phase ID provided');
        }

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');
            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException('Invalid project ID provided');
        }

        $status = $options['status'] ?? null;
        if ($status && !($status instanceof WorkerStatus))
            throw new InvalidArgumentException('Status must be an instance of WorkerStatus enum');


        $paramOptions = [
            'excludeTaskTerminated' => $options['excludeTaskTerminated'] ?? false,
            'limit'                 => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'                => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $where = [];
            $params = [];
            $groupBy = '';

            // Base query structure depends on status
            if ($status === WorkerStatus::UNASSIGNED) {
                // For unassigned: start with project workers, then filter out those on tasks
                if (!$projectId) throw new InvalidArgumentException('Project ID is required');

                $query =
                    "SELECT 
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
                        GROUP_CONCAT(DISTINCT jt.title) AS job_titles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `project_worker` AS pw
                    ON
                        u.id = pw.worker_id
                    AND 
                        pw.project_id = " . (\is_int($projectId)
                        ? ":projectIdJoin"
                        : "(SELECT id FROM `project` WHERE public_id = :projectIdJoin)") . "
                    AND 
                        pw.status = :assignedProjectStatus
                    LEFT JOIN
                        `job_title` AS jt
                    ON
                        u.id = jt.user_id";

                $params[':projectIdJoin'] = \is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
                $params[':assignedProjectStatus'] = WorkerStatus::ASSIGNED->value;

                if ($taskId && ($options['excludeTaskTerminated'])) {
                    // Exclude workers terminated from this specific task
                    $where[] =
                        "NOT EXISTS (
                            SELECT 1
                            FROM 
                                `task_worker` AS tw3
                            WHERE 
                                tw3.worker_id = u.id
                            AND 
                                tw3.task_id = " . (\is_int($taskId)
                            ? ":taskIdTermCheck"
                            : "(SELECT id FROM `task` WHERE public_id = :taskIdTermCheck)") . "
                            AND 
                                tw3.status = :terminatedStatus
                        )";
                    $params[':terminatedStatus'] = WorkerStatus::TERMINATED->value;
                    $params[':taskIdTermCheck'] = \is_int($taskId)
                        ? $taskId
                        : UUID::toBinary($taskId);
                }
            } else {
                // For assigned/terminated: query task workers directly
                $query =
                    "SELECT 
                        u.id,
                        u.public_id,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        u.birth_date,
                        u.gender,
                        u.email,
                        u.contact_number,
                        pw.default_rate,
                        r.unit_rate,
                        tw.estimated_hour,
                        tw.actual_hour,
                        tw.status,
                        u.created_at,
                        u.confirmed_at,
                        u.deleted_at,
                        u.profile_link,
                        GROUP_CONCAT(DISTINCT jt.title) AS job_titles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `task_worker` AS tw
                    ON
                        u.id = tw.worker_id
                    INNER JOIN 	
                        `resource` AS r
                    ON 
                        r.task_worker_id = tw.id
                    INNER JOIN 
                        `project_worker` AS pw
                    ON
                        pw.worker_id = u.id
                    INNER JOIN
                        `task` AS t
                    ON
                        t.id = tw.task_id
                    INNER JOIN
                        `phase` AS ph
                    ON
                        ph.id = t.phase_id
                    INNER JOIN
                        `project` AS p
                    ON
                        p.id = ph.project_id
                    LEFT JOIN
                        `job_title` AS jt
                    ON
                        u.id = ujt.user_id";

                if ($taskId) {
                    $where[] = \is_int($taskId)
                        ? "t.id = :taskId"
                        : "t.public_id = :taskId";
                    $params[':taskId'] = \is_int($taskId)
                        ? $taskId
                        : UUID::toBinary($taskId);
                }

                if ($phaseId) {
                    $where[] = \is_int($phaseId)
                        ? "t.phase_id = :phaseId"
                        : "t.phase_id = (SELECT id FROM `phase` WHERE public_id = :phaseId)";
                    $params[':phaseId'] = \is_int($phaseId)
                        ? $phaseId
                        : UUID::toBinary($phaseId);
                }

                if ($projectId) {
                    $where[] = \is_int($projectId)
                        ? "p.id = :projectId"
                        : "p.id = (SELECT id FROM `project` WHERE public_id = :projectId)";
                    $params[':projectId'] = \is_int($projectId)
                        ? $projectId
                        : UUID::toBinary($projectId);
                }

                if ($status) {
                    $where[] = "tw.status = :status";
                    $params[':status'] = $status->value;
                }

                $groupBy = ", pw.default_rate, r.unit_rate, tw.estimated_hour, tw.actual_hour, tw.status";
            }

            // Full-text search (applies to both queries)
            if (trimOrNull($key)) {
                $where[] = "MATCH(u.first_name, u.middle_name, u.last_name, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Role filter (applies to both queries)
            $where[] = "u.role = :role";
            $params[':role'] = Role::WORKER->value;

            $where[] = "u.confirmed_at IS NOT NULL AND u.deleted_at IS NULL";

            if (!empty($where))
                $query .= " WHERE " . implode(' AND ', $where);

            $query .= " 
                GROUP BY 
                    u.id " . $groupBy . "
                ORDER BY 
                    u.last_name ASC
                LIMIT "
                . \intval($paramOptions['limit']) .
                " OFFSET "
                . \intval($paramOptions['offset']);

            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$this->hasData($result)) return null;

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['job_titles'] = $row['job_titles']
                    ? explode(',', $row['job_titles'])
                    : [];
                $workers->add(TaskWorker::createPartial($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves a paginated list of all workers.
     *
     * @param array $options Supported keys: offset, limit
     */
    public function all(array $options = [
        'offset' => 0,
        'limit'  => 10,
    ]): WorkerContainer|null {
        $offset = (int) ($options['offset'] ?? 0);
        if ($offset < 0) throw new InvalidArgumentException('Invalid offset value');

        $limit = (int) ($options['limit'] ?? 10);
        if ($limit < 1) throw new InvalidArgumentException('Invalid limit value');

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

    public function worksOn(
        int|UUID $taskId,
        int|UUID $userId,
        array $options = [
            'projectId' => null,
    ]): bool {
        if (\is_int($taskId) && $taskId < 1)
            throw new InvalidArgumentException('Invalid task ID provided');
        if (\is_int($userId) && $userId < 1)
            throw new InvalidArgumentException('Invalid user ID provided');

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');
            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException('Invalid project ID provided');
        }

        try {
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
                $projectJoin =
                    "INNER JOIN
                        `phase` AS ph
                    ON
                        ph.id = t.phase_id
                    INNER JOIN
                        `project` AS p
                    ON
                        p.id = ph.project_id";
                $params[':projectId'] = ($projectId instanceof UUID)
                    ? UUID::toBinary($projectId)
                    : $projectId;
            }

            $query =
                "SELECT *
                FROM 
                    `task_worker` AS tw
                INNER JOIN
                    `task` AS t
                ON
                    t.id = tw.task_id
                INNER JOIN
                    `user` AS u
                ON
                    u.id = tw.worker_id
                " . $projectJoin . "
                WHERE 
                    " . (\is_int($taskId) ? 't.id' : 't.public_id') . " = :taskId
                AND 
                    " . (\is_int($userId) ? 'u.id' : 'u.public_id') . " = :userId
                    " . ($projectId ? "AND " . (\is_int($projectId) ? 'p.id' : 'p.public_id') . " = :projectId" : '') . "
                AND 
                    tw.status != :terminatedStatus";

            $statement = $this->connection->prepare($query);
            $statement->execute($params);

            return $this->hasData($statement->fetchAll());
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Creates one or more task-worker associations.
     *
     * Mirrors the PhaseModel::create() style:
     * - Validates taskId
     * - Ensures WorkerContainer is non-empty
     * - Prepares the INSERT once and executes it for each worker
     *
     * Status is set to WorkerStatus::ASSIGNED for backward compatibility with createMultiple().
     */
    public function create(
        int|UUID $taskId, 
        TaskWorker|WorkerContainer $taskWorker
    ): WorkerContainer {
        if (\is_int($taskId) && $taskId < 1)
            throw new InvalidArgumentException('Invalid task ID provided');

        $isBatch = $taskWorker instanceof WorkerContainer;
        $taskWorkers = $isBatch ? $taskWorker : new WorkerContainer([$taskWorker]);
        if ($taskWorkers->count() === 0)
            throw new InvalidArgumentException('WorkerContainer cannot be empty');

        try {
            $isTaskInt = \is_int($taskId);
            $isWorkerInt = !TemporaryId::isTemporary($taskWorkers->first()->getId());

            $insertQuery =
                "INSERT INTO `task_worker` (
                    task_id,
                    worker_id,
                    status,
                    estimated_hour
                ) VALUES (
                    " . ($isTaskInt
                        ? ":taskId"
                        : "(SELECT id FROM `task` WHERE public_id = :taskId)") . ",
                    " . ($isWorkerInt
                        ? ":workerId"
                        : "(SELECT id FROM `user` WHERE public_id = :workerId)") . ",
                    :status,
                    :estimatedHour
                )
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status)";

            $statement = $this->connection->prepare($insertQuery);

            $taskIdParam = ($taskId instanceof UUID) ? UUID::toBinary($taskId) : $taskId;
            foreach ($taskWorkers as &$worker) {
                $statement->execute([
                    ':taskId'           => $taskIdParam,
                    ':workerId'         => $isWorkerInt
                        ? $worker->getId()
                        : UUID::toBinary($worker->getPublicId()),
                    ':status'           => WorkerStatus::ASSIGNED->value,
                    ':estimatedHour'    => $worker->getEstimatedHour(),
                ]);

                $worker->setId($this->connection->lastInsertId());
            }

            return $taskWorkers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Saves updates to a task-worker association in the database.
     * 
     * This method updates fields of a task-worker association based on the provided data array.
     * It supports identifying the association by either its internal ID or by a combination
     * of task ID and worker ID. The method constructs an UPDATE SQL query dynamically
     * based on which fields are present in the data array.
     * 
     * @param array $data Associative array containing the fields to update with the following
     * keys:
     *      - id: (optional) int Internal ID of the task-worker association.
     *      - taskId: (optional) int|string|UUID Task identifier (internal ID or public UUID).
     *      - workerId: (optional) int|string|UUID Worker identifier (internal ID or public UUID).
     *      - status: (optional) WorkerStatus|string New status of the task-worker association.
     *      - estimatedHour: (optional) float New estimated hours for the task-worker association.
     *      - actualHour: (optional) float New actual hours for the task-worker association.
     *      - unitRate: (optional) float New unit rate for the task-worker association.
     * 
     * @return bool True on successful update.
     * @throws PDOException If a database error occurs during the operation.
     * @throws InvalidArgumentException If required identifiers are missing or invalid.
     * @throws DatabaseException If a database error occurs during the operation (wraps PDOException
     */
    public function save(array $taskWorkers): bool
    {
        if (empty($taskWorkers))
            throw new InvalidArgumentException('Task worker array cannot be empty');

        $isBatch = isAssociativeArray($taskWorkers) ? false : true;
        if (!$isBatch) $taskWorkers = [$taskWorkers];

        try {
            foreach ($taskWorkers as $item) {
                if (!\is_array($item))
                    throw new InvalidArgumentException('Each worker update item must be an array');

                $updateFields = [];
                $params = [];

                // Determine identifier clause: prefer numeric/internal id when provided
                if (isset($item['id'])) {
                    if (!\is_int($item['id']) && !is_numeric($item['id']))
                        throw new InvalidArgumentException('Invalid task worker ID provided');

                    $where = 'id = :id';
                    $params[':id'] = (int) $item['id'];
                } else {
                    if (!isset($item['taskId']) || !isset($item['workerId']))
                        throw new InvalidArgumentException('Task ID and Worker ID are required');

                    if (\is_int($item['taskId']) && $item['taskId'] < 1)
                        throw new InvalidArgumentException('Invalid task ID provided');
                    if (\is_int($item['workerId']) && $item['workerId'] < 1)
                        throw new InvalidArgumentException('Invalid worker ID provided');

                    $whereParts = [];
                    if ($item['taskId'] instanceof UUID) {
                        $whereParts[] = 'task_id = (SELECT id FROM `task` WHERE public_id = :taskPublicId)';
                        $params[':taskPublicId'] = UUID::toBinary($item['taskId']);
                    } else {
                        $whereParts[] = 'task_id = :taskId';
                        $params[':taskId'] = $item['taskId'];
                    }

                    if ($item['workerId'] instanceof UUID) {
                        $whereParts[] = 'worker_id = (SELECT id FROM `user` WHERE public_id = :workerPublicId)';
                        $params[':workerPublicId'] = UUID::toBinary($item['workerId']);
                    } else {
                        $whereParts[] = 'worker_id = :workerId';
                        $params[':workerId'] = $item['workerId'];
                    }

                    $where = implode(' AND ', $whereParts);
                }

                if (isset($item['status'])) {
                    $updateFields[] = 'status = :status';
                    $params[':status'] = ($item['status'] instanceof WorkerStatus)
                        ? $item['status']->value
                        : $item['status'];
                }

                if (isset($item['estimatedHour'])) {
                    $updateFields[] = 'estimated_hour = :estimatedHour';
                    $params[':estimatedHour'] = $item['estimatedHour'];
                }

                if (isset($item['actualHour'])) {
                    $updateFields[] = 'actual_hour = :actualHour';
                    $params[':actualHour'] = $item['actualHour'];
                }

                if (!empty($updateFields)) {
                    $query = 'UPDATE `task_worker` SET ' . implode(', ', $updateFields) . ' WHERE ' . $where;
                    $statement = $this->connection->prepare($query);
                    $statement->execute($params);
                }

                if (isset($item['unitRate'])) {
                    if (isset($item['id']))
                        $this->saveUnitRateByTaskWorkerId((int) $item['id'], (float) $item['unitRate']);
                    else
                        $this->saveUnitRateByTaskAndWorker($item['taskId'], $item['workerId'], (float) $item['unitRate']);
                }
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Saves the unit rate for a specific task-worker association.
     *
     * This private method updates the unit rate in the task_resource table
     * for a given task and worker. It supports identifying the task and worker
     * by either their internal IDs or public UUIDs.
     *
     * @param int|UUID $taskId The unique identifier of the task (integer or UUID).
     * @param int|UUID $taskWorkerId The unique identifier of the task worker (integer or UUID).
     * @param float $unitRate The unit rate to be set for the task-worker association.
     *
     * @return bool True on successful update.
     *
     * @throws PDOException If a database error occurs during the operation.
     */
    private function saveUnitRateByTaskWorkerId(int $taskWorkerId, float $unitRate): bool
    {
        try {
            $query =
                "UPDATE 
                    `resource` 
                SET 
                    unit_rate = :unitRate 
                WHERE 
                    task_worker_id = :taskWorkerId";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':unitRate'     => $unitRate,
                ':taskWorkerId' => $taskWorkerId,
            ]);

            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    private function saveUnitRateByTaskAndWorker(
        int|UUID $taskId, 
        int|UUID $workerId, 
        float $unitRate
    ): bool {
        try {
            $taskIdClause = \is_int($taskId)
                ? ':taskId'
                : '(SELECT id FROM `task` WHERE public_id = :taskId)';

            $workerIdClause = \is_int($workerId)
                ? ':workerId'
                : '(SELECT id FROM `user` WHERE public_id = :workerId)';

            $taskWorkerIdClause =
                '(SELECT id FROM `task_worker` WHERE task_id = ' . $taskIdClause . ' AND worker_id = ' . $workerIdClause . ' LIMIT 1)';

            $query =
                "UPDATE `resource`
                SET unit_rate = :unitRate
                WHERE task_id = {$taskIdClause}
                AND task_worker_id = {$taskWorkerIdClause}";

            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':unitRate' => $unitRate,
                ':taskId'   => ($taskId instanceof UUID) ? UUID::toBinary($taskId) : $taskId,
                ':workerId' => ($workerId instanceof UUID) ? UUID::toBinary($workerId) : $workerId,
            ]);

            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Deletes a task-worker association from the taskWorker table.
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
    public function delete(mixed $data): bool
    {
        if (!isset($data['taskId'])) throw new InvalidArgumentException('Task ID is required');
        if (\is_int($data['taskId']) && $data['taskId'] < 1)
            throw new InvalidArgumentException('Invalid task ID provided');
        if (!isset($data['workerId'])) throw new InvalidArgumentException('Worker ID is required');
        if (\is_int($data['workerId']) && $data['workerId'] < 1)
            throw new InvalidArgumentException('Invalid worker ID provided');

        try {
            $query =
                "DELETE FROM
                    `task_worker`
                WHERE 
                    task_id = " . (\is_int($data['taskId']) ? ':taskId' : '(
                        SELECT 
                            id 
                        FROM 
                            `task` 
                        WHERE 
                            public_id = :taskId) ') . "
                AND 
                    worker_id = " . (\is_int($data['workerId']) ? ':workerId' : '(
                        SELECT 
                            id 
                        FROM 
                            `user` 
                        WHERE
                            public_id = :workerId)') . "";


            $statement = $this->connection->prepare($query);
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
