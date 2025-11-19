<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Entity\User;
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
        $instance = new self();
        try {
            $queryString = "
                SELECT 
                    u.id,
                    u.publicId,
                    u.firstName,
                    u.middleName,
                    u.lastName,
                    u.bio,
                    u.gender,
                    u.email,
                    u.contactNumber,
                    u.profileLink,
                    ptw.status,
                    u.createdAt,
                    u.confirmedAt,
                    u.deletedAt,
                    GROUP_CONCAT(ujt.title) AS jobTitles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phaseTaskWorker` AS ptw
                        WHERE 
                            ptw.workerId = u.id
                    ) AS totalTasks,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phaseTaskWorker` AS ptw
                        INNER JOIN 
                            `phaseTask` AS pt 
                        ON 
                            ptw.taskId = pt.id
                        WHERE 
                            ptw.workerId = u.id AND pt.status = '" . WorkStatus::COMPLETED->value . "'
                    ) AS completedTasks
                FROM
                    `user` AS u
                INNER JOIN
                    `phaseTaskWorker` AS ptw 
                ON 
                    u.id = ptw.workerId
                INNER JOIN
                    `phaseTask` AS pt
                ON
                    pt.id = ptw.taskId
                INNER JOIN
                    `projectPhase` AS pp
                ON
                    pp.id = pt.phaseId
                INNER JOIN
                    `project` AS p
                ON
                    p.id = pp.projectId
                LEFT JOIN
                    `userJobTitle` AS ujt
                ON 
                    u.id = ujt.userId
            ";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause),
                $options
            );

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['jobTitles'] = explode(',', $row['jobTitles']);
                $row['additionalInfo'] = [
                    'totalTasks'        => (int) $row['totalTasks'],
                    'completedTasks'    => (int) $row['completedTasks']
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
    public static function findById(int|UUID $workerId, int|UUID|null $taskId = null,  int|UUID|null $phaseId = null, int|UUID|null $projectId = null): ?Worker
    {
        if (is_int($workerId) && $workerId < 1) {
            throw new InvalidArgumentException('Invalid workerId provided.');
        }

        if (is_int($phaseId) && $phaseId < 1) {
            throw new InvalidArgumentException('Invalid phaseId provided.');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid projectId provided.');
        }

        if ($taskId && is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid taskId provided.');
        }

        try {
            $whereClause = is_int($workerId)
                ? 'ptw.workerId = :workerId'
                : 'u.publicId = :workerId';
            $params = [':workerId' => is_int($workerId) ? $workerId : UUID::toBinary($workerId)];

            if ($taskId) {
                $whereClause .= is_int($taskId)
                    ? ' AND ptw.taskId = :taskId'
                    : ' AND pt.publicId = :taskId';
                $params[':taskId'] = is_int($taskId) ? $taskId : UUID::toBinary($taskId);
            }

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pt.phaseId = :phaseId'
                    : ' AND pt.phaseId IN (SELECT id FROM `projectPhase` WHERE publicId = :phaseId)';
                $params[':phaseId'] = is_int($phaseId) ? $phaseId : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND pp.projectId = :projectId'
                    : ' AND pp.projectId IN (SELECT id FROM `project` WHERE publicId = :projectId)';
                $params[':projectId'] = is_int($projectId) ? $projectId : UUID::toBinary($projectId);
            }

            $options = ['limit' => 1];

            $worker = self::find($whereClause, $params, $options);
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
    ): ?WorkerContainer
    {
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

            $workerIdColumn = $useIntId ? "ptw.workerId" : "u.publicId";
            $whereClause = "$workerIdColumn IN (" . implode(', ', $workerIdPlaceholders) . ")";

            if ($taskId) {
                $whereClause .= is_int($taskId)
                    ? ' AND ptw.taskId = :taskId'
                    : ' AND pt.publicId = :taskId';
                $params[':taskId'] = is_int($taskId) ? $taskId : UUID::toBinary($taskId);
            }

            if ($phaseId) {
                $whereClause .= is_int($phaseId)
                    ? ' AND pt.phaseId = :phaseId'
                    : ' AND pt.phaseId IN (SELECT id FROM `projectPhase` WHERE publicId = :phaseId)';
                $params[':phaseId'] = is_int($phaseId) ? $phaseId : UUID::toBinary($phaseId);
            }

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND pp.projectId = :projectId'
                    : ' AND pp.projectId IN (SELECT id FROM `project` WHERE publicId = :projectId)';
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
     * - If $taskId is a UUID, it uses the publicId field and converts the UUID to binary.
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
                ? 'ptw.taskId = :taskId'
                : 'pt.publicId = :taskId';
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
     * @throws InvalidArgumentException If projectId is not provided when searching for unassigned workers.
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
        $options = [
            'excludeTaskTerminated' => false,
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?WorkerContainer {
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
                        u.publicId,
                        u.firstName,
                        u.middleName,
                        u.lastName,
                        u.birthDate,
                        u.gender,
                        u.email,
                        u.contactNumber,
                        GROUP_CONCAT(DISTINCT ujt.title) AS jobTitles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `projectWorker` AS pw
                    ON
                        u.id = pw.workerId
                    AND pw.projectId = " . (is_int($projectId) 
                        ? ":projectIdJoin" 
                        : "(SELECT id FROM `project` WHERE publicId = :projectIdJoin)") . "
                    AND 
                        pw.status = :assignedProjectStatus
                    LEFT JOIN
                        `userJobTitle` AS ujt
                    ON
                        u.id = ujt.userId
                ";

                $params[':projectIdJoin'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
                $params[':assignedProjectStatus'] = WorkerStatus::ASSIGNED->value;
                $params[':assignedStatus'] = WorkerStatus::ASSIGNED->value;
                $params[':completedStatus'] = WorkStatus::COMPLETED->value;
                $params[':cancelledStatus'] = WorkStatus::CANCELLED->value;

                // Exclude workers assigned to ongoing tasks in this project
                $where[] = "NOT EXISTS (
                    SELECT 1
                    FROM 
                        `phaseTaskWorker` AS ptw2
                    INNER JOIN 
                        `phaseTask` AS pt2 
                    ON 
                        ptw2.taskId = pt2.id
                    INNER JOIN 
                        `projectPhase` AS pp2
                    ON
                        pp2.id = pt2.phaseId
                    WHERE 
                        ptw2.workerId = u.id
                    AND 
                        ptw2.status = :assignedStatus
                    AND 
                        pt2.status NOT IN (:completedStatus, :cancelledStatus)
                    AND 
                        pp2.projectId = pw.projectId
                )";

                if ($taskId && ($options['excludeTaskTerminated'])) {
                    // Exclude workers terminated from this specific task
                    $where[] = "NOT EXISTS (
                        SELECT 1
                        FROM 
                            `phaseTaskWorker` AS ptw3
                        WHERE 
                            ptw3.workerId = u.id
                        AND 
                            ptw3.taskId = " . (is_int($taskId) 
                            ? ":taskIdTermCheck" 
                            : "(SELECT id FROM `phaseTask` WHERE publicId = :taskIdTermCheck)") . "
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
                        u.publicId,
                        u.firstName,
                        u.middleName,
                        u.lastName,
                        u.birthDate,
                        u.gender,
                        u.email,
                        u.contactNumber,
                        ptw.status,
                        u.createdAt,
                        u.confirmedAt,
                        u.deletedAt,
                        GROUP_CONCAT(DISTINCT ujt.title) AS jobTitles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `phaseTaskWorker` AS ptw
                    ON
                        u.id = ptw.workerId
                    INNER JOIN
                        `phaseTask` AS pt
                    ON
                        pt.id = ptw.taskId
                    INNER JOIN
                        `projectPhase` AS pp
                    ON
                        pp.id = pt.phaseId
                    INNER JOIN
                        `project` AS p
                    ON
                        p.id = pp.projectId
                    LEFT JOIN
                        `userJobTitle` AS ujt
                    ON
                        u.id = ujt.userId
                ";

                if ($taskId) {
                    $where[] = is_int($taskId)
                        ? "pt.id = :taskId"
                        : "pt.publicId = :taskId";
                    $params[':taskId'] = is_int($taskId)
                        ? $taskId
                        : UUID::toBinary($taskId);
                }

                if ($phaseId) {
                    $where[] = is_int($phaseId)
                        ? "pt.phaseId = :phaseId"
                        : "pt.phaseId = (SELECT id FROM `projectPhase` WHERE publicId = :phaseId)";
                    $params[':phaseId'] = is_int($phaseId)
                        ? $phaseId
                        : UUID::toBinary($phaseId);
                }

                if ($projectId) {
                    $where[] = is_int($projectId)
                        ? "p.id = :projectId"
                        : "p.id = (SELECT id FROM `project` WHERE publicId = :projectId)";
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
                $where[] = "MATCH(u.firstName, u.middleName, u.lastName, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Role filter (applies to both queries)
            $where[] = "u.role = :role";
            $params[':role'] = Role::WORKER->value;

            $where[] = "u.confirmedAt IS NOT NULL AND u.deletedAt IS NULL";

            if (!empty($where)) {
                $query .= " WHERE " . implode(' AND ', $where);
            }
            $query .= " GROUP BY u.id";

            // Pagination
            if (isset($options['limit'])) {
                $query .= " LIMIT " . intval($options['limit']);
            }
            if (isset($options['offset'])) {
                $query .= " OFFSET " . intval($options['offset']);
            }

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['jobTitles'] = $row['jobTitles'] 
                    ? explode(',', $row['jobTitles']) 
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
            $options = [
                'offset'    => $offset,
                'limit'     => $limit,
                'orderBy'   => 'u.createdAt DESC',
            ];  

            return self::find('', [], $options);
        } catch (Exception $e) {
            throw $e;
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
                    `phaseTaskWorker` (taskId, workerId, status)
                VALUES (
                    " . ($isTaskInt 
                        ? ":taskId" 
                        : "(SELECT id FROM `phaseTask` WHERE publicId = :taskId)") . ",
                    " . ($isWorkerInt 
                        ? ":workerId" 
                        : "(SELECT id FROM `user` WHERE publicId = :workerId)") . ",
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
                // Require taskId and workerId when id is not provided
                if (is_int($data['taskId']) && $data['taskId'] < 1) {
                    throw new InvalidArgumentException('Invalid task ID provided.');
                }

                if (is_int($data['workerId']) && $data['workerId'] < 1) {
                    throw new InvalidArgumentException('Invalid worker ID provided.');
                }

                $whereParts = [];
                // taskId may be int or UUID
                if ($data['taskId'] instanceof UUID) {
                    $whereParts[] = 'taskId = (SELECT id FROM `phaseTask` WHERE publicId = :taskPublicId)';
                    $params[':taskPublicId'] = UUID::toBinary($data['taskId']);
                } else {
                    $whereParts[] = 'taskId = :taskId';
                    $params[':taskId'] = $data['taskId'];
                }

                // workerId may be int or UUID
                if ($data['workerId'] instanceof UUID) {
                    $whereParts[] = 'workerId = (SELECT id FROM `user` WHERE publicId = :workerPublicId)';
                    $params[':workerPublicId'] = UUID::toBinary($data['workerId']);
                } else {
                    $whereParts[] = 'workerId = :workerId';
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

            $query = 'UPDATE `phaseTaskWorker` SET ' . implode(', ', $updateFields) . ' WHERE ' . $where;
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