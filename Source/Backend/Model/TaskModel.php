<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Entity\Phase;
use App\Entity\ProjectManager;
use App\Entity\Project;
use App\Exception\DatabaseException;
use App\Enumeration\WorkStatus;
use App\Enumeration\Priority;
use App\Entity\Task;
use App\Utility\TemporaryId;
use Exception;
use InvalidArgumentException;
use PDOException;

class TaskModel extends Model
{
    /**
     * Finds tasks based on a custom WHERE clause and parameters.
     *
     * This method constructs and executes a SQL query to retrieve tasks from the database
     * based on the provided WHERE clause and parameters. It supports various options for
     * pagination, grouping, and ordering of results.
     *
     * @param string $whereClause The SQL WHERE clause to filter tasks.
     * @param array $params The parameters to bind to the SQL query.
     * @param array $options Options for query execution:
     *      - limit: int Maximum number of results to return (default: 50)
     *      - offset: int Number of results to skip (default: 0)
     *      - groupBy: string SQL GROUP BY clause (default: 'pt.id')
     *      - orderBy: string SQL ORDER BY clause (default: 'pt.start_date_time DESC')
     *
     * @return TaskContainer|null A container of found tasks, or null if no tasks match the criteria.
     *
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    protected function find(string $whereClause = '', array $params = [], array $options = []): TaskContainer|null
    {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 't.id',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 't.start_date_time DESC',
        ];

        try {
            $query =
                "SELECT 
                    t.id AS id,
                    t.public_id AS public_id,
                    ph.public_id AS phase_id,
                    t.name AS name,
                    t.description AS description,
                    t.start_date_time AS start_date_time,
                    t.completion_date_time AS completion_date_time,
                    t.actual_completion_date_time AS actual_completion_date_time,
                    t.priority AS priority,
                    tb.estimated_cost AS estimated_cost,
                    tb.actual_cost AS actual_cost,
                    tb.note AS budget_note,
                    t.status AS status,
                    t.created_at AS created_at
                FROM 
                    `task` AS t
                INNER JOIN 
                    `task_budget` AS tb
                ON
                    t.id = tb.task_id
                INNER JOIN 
                    `phase` AS ph 
                ON 
                    t.phase_id = ph.id
                INNER JOIN 
                    `project` AS p 
                ON 
                    ph.project_id = p.id
                LEFT JOIN 
                    `task_worker` AS tw 
                ON 
                    t.id = tw.task_id
                LEFT JOIN 
                    `user` AS u ON tw.worker_id = u.id";
            $projectTaskQuery = $this->appendOptionsToFindQuery(
                $this->appendWhereClause($query, $whereClause),
                $paramOptions
            );

            $statement = $this->connection->prepare($projectTaskQuery);
            $statement->execute($params);
            $results = $statement->fetchAll();

            if (!$this->hasData($results)) return null;

            $tasks = new TaskContainer();
            foreach ($results as $row) {
                $row['additionalInfo'] = ['phaseId' => UUID::fromBinary($row['phase_id'])];
                $tasks->add(Task::createPartial($row));
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
     * @param WorkStatus|null $status Optional work status to filter tasks.
     * @param Priority|null $priority Optional task priority to filter tasks.
     * @param array $options Pagination options:
     *      - limit: int Maximum number of results to return (default: 10)
     *      - offset: int Number of results to skip (default: 0)
     *
     * @return TaskContainer|null Container of found tasks, or null if no tasks match the criteria.
     *
     * @throws InvalidArgumentException If an invalid ID is / are provided.
     * @throws Exception If an error occurs during the search operation.
     */
    public function search(
        string $key = '',
        array $options = [
            'userId'    => null,
            'phaseId'   => null,
            'projectId' => null,
            'status'    => null,
            'priority'  => null,

            'limit'     => 10,
            'offset'    => 0,
        ]
    ): TaskContainer|null {
        $userId = $options['userId'] ?? null;
        if ($userId) {
            if (!\is_int($userId) && !($userId instanceof UUID))
                throw new InvalidArgumentException('User ID must be an integer or UUID');
            if (\is_int($userId) && $userId < 1)
                throw new InvalidArgumentException('Invalid user ID provided');
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
        if ($status && !($status instanceof WorkStatus))
            throw new InvalidArgumentException('Status must be an instance of WorkStatus enum');

        $priority = $options['priority'] ?? null;
        if ($priority && !($priority instanceof Priority))
            throw new InvalidArgumentException('Priority must be an instance of Priority enum');

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $where = [];
            $params = [];

            if (trimOrNull($key)) {
                $where[] = "MATCH(t.name, t.description) AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Filter by user role if provided
            if ($userId) {
                $where[] = is_int($userId)
                    ? ' (p.manager_id = :userId1 OR tw.worker_id = :userId2)'
                    : ' (tw.worker_id IN (
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
                    ? ' t.phase_id = :phaseId'
                    : ' t.phase_id IN (
                        SELECT 
                            id 
                        FROM 
                            `phase` 
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
            if ($status) {
                $where[] = ' t.status = :status';
                $params[':status'] = $status->value;
            }
            if ($priority) {
                $where[] = ' t.priority = :priority';
                $params[':priority'] = $priority->value;
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
    public function findById(
        int|UUID $taskId,
        int|UUID|null $phaseId = null,
        int|UUID|null $projectId = null
    ): Task|null {
        if (\is_int($taskId) && $taskId < 1)
            throw new InvalidArgumentException('Invalid task ID');
        if ($phaseId && \is_int($phaseId) && $phaseId < 1)
            throw new InvalidArgumentException('Invalid phase ID');

        try {
            $whereClause = \is_int($taskId)
                ? 't.id = :taskId'
                : 't.public_id = :taskId';
            $params = [
                ':taskId' => \is_int($taskId)
                    ? $taskId
                    : UUID::toBinary($taskId)
            ];

            if ($phaseId) {
                $whereClause .= \is_int($phaseId)
                    ? ' AND ph.id = :phaseId'
                    : ' AND ph.public_id = :phaseId';
                $params[':phaseId'] = \is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $whereClause .= \is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.public_id = :projectId';
                $params[':projectId'] = \is_int($projectId)
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
     * @param WorkStatus|Priority|null $filter (optional) Filter tasks by work status or priority.
     * @param array $options (optional) Pagination options:
     *      - offset: int The starting index for results (default: 0).
     *      - limit: int The maximum number of results to return (default: 10).
     *
     * @throws InvalidArgumentException If the provided phase_id is invalid.
     * @throws Exception If an error occurs during query execution.
     *
     * @return TaskContainer|null A container of tasks matching the criteria, or null if none found.
     */
    public function findByPhaseId(
        int|UUID $phaseId,
        array $options = [
            'projectId' => null,
            'status'    => null,
            'priority'  => null,

            'offset'    => 0,
            'limit'     => 10,
        ]
    ): TaskContainer|null {
        if (\is_int($phaseId) && $phaseId < 1)
            throw new InvalidArgumentException('Invalid phase ID');

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');
            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException('Invalid project ID provided');
        }

        $status = $options['status'] ?? null;
        if ($status && !($status instanceof WorkStatus))
            throw new InvalidArgumentException('Status must be an instance of WorkStatus enum');

        $priority = $options['priority'] ?? null;
        if ($priority && !($priority instanceof Priority))
            throw new InvalidArgumentException('Priority must be an instance of Priority enum');

        try {
            $whereClause = \is_int($phaseId)
                ? 't.phase_id = :phaseId'
                : 't.phase_id IN (
                    SELECT 
                        id 
                    FROM 
                        `phase` 
                    WHERE 
                        public_id = :phaseId)';
            $params = [
                ':phaseId' => \is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId)
            ];

            if ($projectId) {
                $whereClause .= \is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.public_id = :projectId';
                $params[':projectId'] = \is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            if ($status) {
                $whereClause .= ' AND t.status = :status';
                $params[':status'] = $status->value;
            }

            if ($priority) {
                $whereClause .= ' AND t.priority = :priority';
                $params[':priority'] = $priority->value;
            }

            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds all tasks for multiple phase IDs in a single query.
     *
     * This method efficiently retrieves tasks for multiple phases at once,
     * avoiding the N+1 query problem. Returned tasks include their phase_id
     * for grouping.
     *
     * @param array $phaseIds Array of integer phase IDs
     * @param array $options Query options (limit, offset, etc.)
     * 
     * @return TaskContainer|null Container with tasks, or null if none found
     * 
     * @throws InvalidArgumentException If phaseIds array is empty
     * @throws DatabaseException If a database error occurs
     */
    public function findByPhaseIds(
        array $phaseIds,
        array $options = [
            'status'    => null,
        ]
    ): TaskContainer|null {
        if (empty($phaseIds)) throw new InvalidArgumentException('Phase IDs array cannot be empty');

        try {
            $params = [];

            $placeholders = '';
            foreach ($phaseIds as $index => $id) {
                $placeholders .= ':phaseId' . $index . ',';
                $params[':phaseId' . $index] = $id;
            }
            $whereClause = "t.phase_id IN (" . rtrim($placeholders, ',') . ")";

            $status = $options['status'] ?? null;
            if ($status) {
                if (!($status instanceof WorkStatus))
                    throw new InvalidArgumentException('Status must be an instance of WorkStatus enum');

                $whereClause .= ' AND t.status = :status';
                $params[':status'] = $status->value;
            }

            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function findByProjectId(
        int|UUID $projectId,
        array $options = [
            'limit'  => 10,
            'offset' => 0,
        ]
    ): TaskContainer|null {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID');
        try {
            return $this->search(
                '',
                [
                    'projectId' => $projectId,
                    'limit'     => $options['limit'] ?? 10,
                    'offset'    => $options['offset'] ?? 0,
                ]
            );
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
     * @param WorkStatus|Priority|null $filter Optional filter to narrow down tasks by status or priority.
     * @param array $options (Optional) Query options:
     *      - offset: int (default 0) The number of records to skip.
     *      - limit: int (default 10) The maximum number of records to return.
     *
     * @throws InvalidArgumentException If the worker or project ID is invalid.
     * @throws Exception If an error occurs during the query.
     *
     * @return TaskContainer|null A container with the found tasks, or null if none found.
     */
    public function findAssignedToWorker(
        int|UUID $workerId,
        int|UUID|null $projectId = null,
        WorkStatus|Priority|null $filter = null,
        array $options = [
            'offset' => 0,
            'limit' => 10,
        ]
    ): TaskContainer|null {
        if (\is_int($workerId) && $workerId < 1)
            throw new InvalidArgumentException('Invalid worker ID');
        if ($projectId && \is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID');

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $whereClause = \is_int($workerId)
                ? 'u.id = :workerId'
                : 'u.public_id = :workerId';
            $params = [
                ':workerId' => \is_int($workerId)
                    ? $workerId
                    : UUID::toBinary($workerId),
            ];

            if ($projectId) {
                $whereClause .= \is_int($projectId)
                    ? ' AND p.id = :projectId'
                    : ' AND p.public_id = :projectId';
                $params[':projectId'] = \is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            if ($filter instanceof WorkStatus) {
                $whereClause .= ' AND t.status = :status';
                $params[':status'] = $filter->value;
            } elseif ($filter instanceof Priority) {
                $whereClause .= ' AND t.priority = :priority';
                $params[':priority'] = $filter->value;
            }

            return self::find($whereClause, $params, $paramOptions);
        } catch (Exception $e) {
            throw $e;
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
    public function findOwningProject(int|UUID $taskId): Project|null
    {
        if (\is_int($taskId) && $taskId < 1)
            throw new InvalidArgumentException('Invalid task ID provided');

        try {
            $query =
                "SELECT 
                    p.*,
                    u.id AS u_id,
                    u.public_id AS u_public_id,
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
                    `phase` AS ph
                ON
                    p.id = ph.project_id
                INNER JOIN
                    `task` AS t
                ON
                    ph.id = t.phase_id
                WHERE 
                    " . (is_int($taskId)
                    ? 't.id = :taskId'
                    : 't.public_id = :taskId') . "
                LIMIT 1";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':taskId' => is_int($taskId)
                    ? $taskId
                    : UUID::toBinary($taskId)
            ]);
            $result = $statement->fetch();

            if (!$this->hasData($result)) return null;

            $result['manager'] = ProjectManager::createPartial([
                'id'           => $result['u_id'],
                'publicId'     => $result['u_public_id'],
                'firstName'    => $result['first_name'],
                'middleName'   => $result['middle_name'],
                'lastName'     => $result['last_name'],
                'gender'       => $result['gender'],
                'email'        => $result['email'],
                'profileLink'  => $result['profile_link'],
            ]);
            $project = Project::createPartial($result);

            return $project;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds and returns the Phase that owns a given Task.
     *
     * This method retrieves the project phase associated with the specified task ID (either integer or UUID).
     * It joins through phase_task -> project_phase tables to fetch phase details along with its budget information.
     * Returns a partial Phase instance, or null if not found.
     *
     * @param int|UUID $taskId The ID or public UUID of the task whose owning phase is to be found.
     *
     * @throws InvalidArgumentException If the provided task ID is invalid.
     * @throws DatabaseException If a database error occurs during the query.
     *
     * @return Phase|null The owning Phase instance, or null if no phase is found for the given task.
     */
    public function findOwningPhase(int|UUID $taskId)
    {
        if (\is_int($taskId) && $taskId < 1)
            throw new InvalidArgumentException('Invalid task ID provided');

        try {
            $query =
                "SELECT
                    ph.*,
                    phb.budget,
                    phb.contingency_rate,
                    phb.note AS budget_note
                FROM
                    `phase` AS ph
                INNER JOIN
                    `phase_budget` AS phb
                ON
                    ph.id = phb.phase_id
                WHERE
                    ph.id = (
                                SELECT 
                                    phase_id
                                FROM
                                    `task`
                                WHERE 
                                    " . (\is_int($taskId)
                    ? 'id = :taskId'
                    : 'public_id = :taskId') . "
                            )";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':taskId' => \is_int($taskId)
                    ? $taskId
                    : UUID::toBinary($taskId)
            ]);
            $result = $statement->fetch();
            if (!$this->hasData($result)) return null;

            $phase = Phase::createPartial($result);
            return $phase;
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
    public function all(int $offset = 0, int $limit = 10): TaskContainer|null
    {
        if ($offset < 0) throw new InvalidArgumentException('Invalid offset value');
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

    public function create(int|UUID $phaseId, Task|TaskContainer $task): Task|TaskContainer
    {
        if (\is_int($phaseId) && $phaseId < 1)
            throw new InvalidArgumentException('Invalid phase ID provided');

        $isBatch = $task instanceof TaskContainer;
        $tasks = $isBatch ? $task : new TaskContainer([$task]);
        if ($tasks->count() === 0) throw new InvalidArgumentException('TaskContainer cannot be empty');

        try {
            $query =
                "INSERT INTO `task` (
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
                    " . (\is_int($phaseId)
                        ? ":phaseId"
                        : "(SELECT id FROM `phase` WHERE public_id = :phaseId)") . ",
                    :name,
                    :description,
                    :priority,
                    :status,
                    :startDateTime,
                    :completionDateTime
                )";

            $statement = $this->connection->prepare($query);
            foreach ($tasks as $task) {
                $phaseId = $task->getAdditionalInfo('phaseId'); // Required

                if (!\is_int($phaseId) && !($phaseId instanceof UUID))
                    throw new InvalidArgumentException('Phase ID must be an integer or UUID');
                if (\is_int($phaseId) && $phaseId < 1)
                    throw new InvalidArgumentException('Invalid phase ID provided');

                $taskPublicId = $task->getPublicId() ?? UUID::get();
                $statement->execute([
                    ':publicId'             => UUID::toBinary($taskPublicId),
                    ':phaseId'              => \is_int($phaseId)
                        ? $phaseId
                        : UUID::toBinary($phaseId),
                    ':name'                 => trimOrNull($task->getName()),
                    ':description'          => trimOrNull($task->getDescription()),
                    ':priority'             => $task->getPriority()->value,
                    ':status'               => $task->getStatus()->value,
                    ':startDateTime'        => formatDateTime($task->getStartDateTime()),
                    ':completionDateTime'   => formatDateTime($task->getCompletionDateTime()),
                ]);

                $taskId = (int)$this->connection->lastInsertId();
                $task->setId($taskId);
                $task->setPublicId($taskPublicId);

                $this->createBudget(
                    $taskId,
                    [
                        'estimatedCost'    => $task->getEstimatedCost(),
                        'actualCost'       => $task->getActualCost(),
                        'budgetNote'             => $task->getBudgetNote(),
                    ]
                );
            }

            return $isBatch ? $tasks : $tasks->first();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    private function createBudget(int|UUID $taskId, array $data): void
    {
        if (\is_int($taskId) && $taskId < 1)
            throw new InvalidArgumentException('Invalid task ID');

        try {
            // Insert budget record
            $budgetQuery =
                "INSERT INTO `task_budget` (
                    task_id,
                    estimated_cost,
                    actual_cost,
                    note
                ) VALUES (
                    " . (\is_int($taskId)
                    ? ':taskId'
                    : '(SELECT id FROM `task` WHERE public_id = :taskId)') . ",
                    :estimatedCost,
                    :actualCost,
                    :note
                )";
            $budgetQueryStatement = $this->connection->prepare($budgetQuery);
            $budgetQueryStatement->execute([
                ':taskId'           => \is_int($taskId) ? $taskId : UUID::toBinary($taskId),
                ':estimatedCost'    => $data['estimatedCost'],
                ':actualCost'       => $data['actualCost'],
                ':note'             => $data['budgetNote'] ? trimOrNull($data['budgetNote']) : null
            ]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Updates an existing task / tasks in the database.
     *
     * Backward compatible:
     * - Accepts a single associative array (existing behavior)
     * - Accepts a list of associative arrays for batch updates
     *
     * Each update item can identify the task by either:
     * - id (int)
     * - publicId (UUID|string)
     *
     * @param array $data A single update array, or a list of update arrays.
     *
     * @throws InvalidArgumentException If invalid data is provided.
     * @throws DatabaseException If a database error occurs during the update operation.
     *
     * @return bool True if all updates were successful.
     */
    public function save(array $tasks): bool
    {
        if (empty($tasks)) throw new InvalidArgumentException('Tasks array cannot be empty');

        $isBatch = array_keys($tasks) === range(0, \count($tasks) - 1);
        if (!$isBatch) $tasks = [$tasks];

        try {
            foreach ($tasks as $item) {
                $updateFields = [];
                $params = [];
                $whereClause = null;

                if (isset($item['id'])) {
                    if (!\is_int($item['id']) || $item['id'] < 1)
                        throw new InvalidArgumentException('Invalid task ID');

                    $params[':id'] = $item['id'];
                    $whereClause = 'id = :id';
                } elseif (isset($item['publicId'])) {
                    $publicId = $item['publicId'];
                    if (is_string($publicId))
                        $publicId = UUID::fromString($publicId);
                    if (!($publicId instanceof UUID))
                        throw new InvalidArgumentException('Public ID must be an instance of UUID');

                    $params[':publicId'] = UUID::toBinary($publicId);
                    $whereClause = 'public_id = :publicId';
                } else {
                    throw new InvalidArgumentException('Task ID or Public ID is required');
                }

                if (isset($item['name'])) {
                    $updateFields[] = 'name = :name';
                    $params[':name'] = trimOrNull($item['name']);
                }

                if (isset($item['description'])) {
                    $updateFields[] = 'description = :description';
                    $params[':description'] = trimOrNull($item['description']);
                }

                if (isset($item['priority'])) {
                    $updateFields[] = 'priority = :priority';
                    $params[':priority'] = $item['priority']->value;
                }

                if (isset($item['status'])) {
                    $updateFields[] = 'status = :status';
                    $params[':status'] = $item['status']->value;
                }

                if (isset($item['startDateTime'])) {
                    $updateFields[] = 'start_date_time = :startDateTime';
                    $params[':startDateTime'] = formatDateTime($item['startDateTime']);
                }

                if (isset($item['completionDateTime'])) {
                    $updateFields[] = 'completion_date_time = :completionDateTime';
                    $params[':completionDateTime'] = formatDateTime($item['completionDateTime']);
                }

                if (isset($item['actualCompletionDateTime'])) {
                    $updateFields[] = 'actual_completion_date_time = :actualCompletionDateTime';
                    $params[':actualCompletionDateTime'] = $item['actualCompletionDateTime'] !== null
                        ? formatDateTime($item['actualCompletionDateTime'])
                        : null;
                }

                if (!empty($updateFields)) {
                    $taskQuery = "UPDATE `task` SET " . implode(', ', $updateFields) . " WHERE {$whereClause}";
                    $statement = $this->connection->prepare($taskQuery);
                    $statement->execute($params);
                }

                $this->saveBudget($item);
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Updates the budget details of a specific task.
     *
     * This method updates the estimated cost, actual cost, and budget note for a task identified by its ID or UUID.
     * It constructs an SQL UPDATE query based on the provided data and executes it against the database.
     *
     * @param array $data An associative array containing budget details to update:
     *      - id: int|null The ID of the task to update.
     *      - estimatedCost: float|null The new estimated cost for the task.
     *      - actualCost: float|null The new actual cost for the task.
     *      - note: string|null A note regarding the budget.
     *
     * @throws InvalidArgumentException If the provided task ID is invalid.
     * @throws PDOException If a database error occurs during the update operation.
     *
     * @return bool True if the update was successful, false otherwise.
     */
    private function saveBudget(array $data): bool
    {
        try {
            $updateFields = [];
            $params = [];

            $whereClause = null;

            if (isset($data['id'])) {
                if (!\is_int($data['id']) || $data['id'] < 1)
                    throw new InvalidArgumentException('Invalid task ID');

                $params[':id'] = $data['id'];
                $whereClause = 'task_id = :id';
            } elseif (isset($data['publicId'])) {
                $publicId = $data['publicId'];
                if (is_string($publicId))
                    $publicId = UUID::fromString($publicId);
                if (!($publicId instanceof UUID))
                    throw new InvalidArgumentException('Public ID must be an instance of UUID');

                $params[':publicId'] = UUID::toBinary($publicId);
                $whereClause = 'task_id = (SELECT id FROM `task` WHERE public_id = :publicId)';
            } else {
                throw new InvalidArgumentException('Task ID or Public ID is required');
            }

            if (isset($data['estimatedCost'])) {
                $updateFields[] = 'estimated_cost = :estimatedCost';
                $params[':estimatedCost'] = $data['estimatedCost'];
            }

            if (isset($data['actualCost'])) {
                $updateFields[] = 'actual_cost = :actualCost';
                $params[':actualCost'] = $data['actualCost'];
            }

            if (isset($data['budgetNote'])) {
                $updateFields[] = 'note = :note';
                $params[':note'] = trimOrNull($data['budgetNote']);
            }

            if (!empty($updateFields)) {
                $budgetQuery = "UPDATE `task_budget` SET " . implode(', ', $updateFields) . " WHERE {$whereClause}";
                $statement = $this->connection->prepare($budgetQuery);
                $statement->execute($params);
            }

            return true;
        } catch (PDOException $e) {
            throw $e;
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
    public function delete(mixed $data): bool
    {
        // Not implemented (No use case)
        return false;
    }
}
