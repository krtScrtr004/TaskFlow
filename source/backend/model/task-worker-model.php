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
                    GROUP_CONCAT(ujt.title) AS jobTitles,
                    (
                        SELECT COUNT(*)
                        FROM projectTaskWorker AS ptw
                        WHERE ptw.workerId = u.id
                    ) AS totalTasks,
                    (
                        SELECT COUNT(*)
                        FROM projectTaskWorker AS ptw
                        INNER JOIN projectTask AS t ON ptw.taskId = t.id
                        WHERE ptw.workerId = u.id AND t.status = '" . WorkStatus::COMPLETED->value . "'
                    ) AS completedTasks
                FROM
                    `user` AS u
                INNER JOIN
                    `projectTaskWorker` AS ptw 
                ON 
                    u.id = ptw.workerId
                INNER JOIN
                    `projectTask` AS pt
                ON
                    pt.id = ptw.taskId
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
                    'completedTasks'    => (int) $row['completedTasks'],
                    'totalProjects'     => (int) $row['totalProjects'],
                    'completedProjects' => (int) $row['completedProjects'],
                ];
                $workers->add(Worker::createPartial($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public static function findById(int|UUID $workerId, int|UUID|null $taskId = null, int|UUID|null $projectId = null): ?Worker
    {
        if (is_int($workerId) && $workerId < 1) {
            throw new InvalidArgumentException('Invalid workerId provided.');
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

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND pt.projectId = :projectId'
                    : ' AND pt.projectId IN (SELECT id FROM `project` WHERE publicId = :projectId)';
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
     * Finds multiple Workers associated with a specific Task by their worker IDs.
     *
     * This method retrieves multiple Worker instances that are linked to the given task,
     * supporting both integer and UUID identifiers for task, project, and workers.
     * If taskId is null, it will search for the workers across all tasks.
     * If projectId is null, it will search across all projects.
     *
     * @param array $workerIds Array of worker identifiers (integers or UUIDs).
     * @param int|UUID|null $taskId The task identifier (integer, UUID, or null for any task).
     * @param int|UUID|null $projectId The project identifier (integer, UUID, or null for any project).
     * 
     * @throws InvalidArgumentException If invalid IDs or empty worker IDs array is provided.
     * @throws DatabaseException If an error occurs during the query.
     * 
     * @return WorkerContainer|null A WorkerContainer with Worker instances if found, or null if not found.
     */
    public static function findMultipleById(
        array $workerIds,
        int|UUID|null $taskId = null,
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

            if ($projectId) {
                $whereClause .= is_int($projectId)
                    ? ' AND pt.projectId = :projectId'
                    : ' AND pt.projectId IN (SELECT id FROM `project` WHERE publicId = :projectId)';
                $params[':projectId'] = is_int($projectId) ? $projectId : UUID::toBinary($projectId);
            }

            return self::find($whereClause, $params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function findByTaskId(int|UUID $taskId): ?WorkerContainer
    {
        if (is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid taskId provided.');
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

    public static function search(
        string|null $key = null,
        int|UUID|null $taskId = null,
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
                    throw new InvalidArgumentException('Project ID is required when searching for unassigned task workers.');
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
                        AND pw.status = :assignedProjectStatus
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
                        `projectTaskWorker` AS ptw2
                    INNER JOIN 
                        `projectTask` AS pt2 
                    ON 
                        ptw2.taskId = pt2.id
                    WHERE 
                        ptw2.workerId = u.id
                    AND 
                        ptw2.status = :assignedStatus
                    AND 
                        pt2.status NOT IN (:completedStatus, :cancelledStatus)
                    AND 
                        pt2.projectId = pw.projectId
                )";

                if ($taskId && ($options['excludeTaskTerminated'] ?? true)) {
                    // Exclude workers terminated from this specific task
                    $where[] = "NOT EXISTS (
                        SELECT 1
                        FROM 
                            `projectTaskWorker` AS ptw3
                        WHERE 
                            ptw3.workerId = u.id
                        AND 
                            ptw3.taskId = " . (is_int($taskId) 
                            ? ":taskIdTermCheck" 
                            : "(SELECT id FROM `projectTask` WHERE publicId = :taskIdTermCheck)") . "
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
                        GROUP_CONCAT(DISTINCT ujt.title) AS jobTitles
                    FROM
                        `user` AS u
                    INNER JOIN
                        `projectTaskWorker` AS ptw
                    ON
                        u.id = ptw.workerId
                    INNER JOIN
                        `projectTask` AS pt
                    ON
                        pt.id = ptw.taskId
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

                if ($projectId) {
                    $where[] = is_int($projectId)
                        ? "pt.projectId = :projectId"
                        : "pt.projectId = (SELECT id FROM `project` WHERE publicId = :projectId)";
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
            if ($key && !empty($key))  {
                $where[] = "MATCH(u.firstName, u.middleName, u.lastName, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Role filter (applies to both queries)
            $where[] = "u.role = :role";
            $params[':role'] = Role::WORKER->value;

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
        if (empty($data)) {
            throw new InvalidArgumentException('No data provided.');
        }

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $isTaskInt = is_int($taskId);
            $isWorkerInt = is_int($data[0]);
            
            $insertQuery = "
                INSERT INTO `projectTaskWorker`
                    (taskId, workerId, status)
                VALUES (
                    " . ($isTaskInt 
                        ? ":taskId" 
                        : "(SELECT id FROM `projectTask` WHERE publicId = :taskId)") . ",
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
                if (!isset($data['taskId'])) {
                    throw new InvalidArgumentException('Task ID must be provided.');
                }

                if (!isset($data['workerId'])) {
                    throw new InvalidArgumentException('Worker ID must be provided.');
                }

                $whereParts = [];
                // taskId may be int or UUID
                if ($data['taskId'] instanceof UUID) {
                    $whereParts[] = 'taskId = (SELECT id FROM `projectTask` WHERE publicId = :taskPublicId)';
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

            $query = 'UPDATE `projectTaskWorker` SET ' . implode(', ', $updateFields) . ' WHERE ' . $where;
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);

            $instance->connection->commit();
            return true;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
	}






    protected static function delete(): bool
    {
        // TODO: Implement logic to delete a task worker
        return false;
    }

}