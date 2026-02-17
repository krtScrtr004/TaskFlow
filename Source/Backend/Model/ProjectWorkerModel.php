<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\ProjectContainer;
use App\Container\TaskContainer;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Entity\Phase;
use App\Entity\Worker;
use App\Entity\Project;
use App\Entity\Task;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use Exception;
use InvalidArgumentException;
use PDOException;

class ProjectWorkerModel extends Model
{
    /**
     * Finds and retrieves worker information based on specified conditions.
     *
     * This method executes a complex SQL query to fetch worker details, including personal information,
     * job titles, project and task statistics, and status. It supports dynamic WHERE clauses, query parameters,
     * and additional query options.
     *
     * The returned data includes:
     * - Worker personal details (public_id, firstName, middleName, lastName, bio, gender, email, contactNumber, profileLink, createdAt, confirmedAt, deletedAt)
     * - Worker status in the project
     * - Aggregated job titles (as an array)
     * - Total and completed tasks assigned to the worker
     * - Total and completed projects the worker is involved in
     *
     * @param string $whereClause Optional SQL WHERE clause to filter results (without the 'WHERE' keyword)
     * @param array $params Parameters to bind to the prepared SQL statement
     * @param array $options Additional options for query customization (e.g., ordering, limits)
     *
     * @return WorkerContainer|null A container of Worker objects matching the criteria, or null if no data found
     *
     * @throws DatabaseException If a database error occurs during query execution
     */
    protected function find(
        string $whereClause = '',
        array $params = [],
        array $options = []
    ): WorkerContainer|null {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'u.id, pw.status, pw.default_rate',
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
                    pw.status,
                    pw.default_rate,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(DISTINCT jt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `task_worker` AS tw
                        WHERE 
                            tw.worker_id = u.id
                    ) AS total_tasks,
                    (
                        SELECT COUNT(*)
                        FROM 
                            `task_worker` AS tw
                        INNER JOIN 
                            `task` AS t 
                        ON 
                            tw.task_id = t.id
                        WHERE 
                            tw.worker_id = u.id
                        AND 
                            t.status = '" . WorkStatus::COMPLETED->value . "'
                    ) AS completed_tasks,
                    (
                        SELECT 
                            COUNT(*) 
                        FROM 
                            `project_worker` AS pw2 
                        WHERE 
                            pw2.worker_id = u.id
                    ) AS total_projects,
                    (
                        SELECT 
                            COUNT(*) 
                        FROM 
                            `project_worker` AS pw3
                        INNER JOIN 
                            `project` AS p2 
                        ON 
                            pw3.project_id = p2.id
                        WHERE 
                            pw3.worker_id = u.id 
                        AND 
                            p2.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            pw3.status != '" . WorkerStatus::TERMINATED->value . "'
                    ) AS completed_projects
                FROM
                    `user` AS u
                INNER JOIN
                    `project_worker` AS pw 
                ON 
                    u.id = pw.worker_id
                INNER JOIN
                    `project` AS p
                ON
                    pw.project_id = p.id
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

            if (!$this->hasData($result)) return null;

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['job_titles'] = explode(',', $row['job_titles']);
                $row['additionalInfo'] = [
                    'total_tasks'        => (int) $row['total_tasks'],
                    'completedTasks'     => (int) $row['completed_tasks'],
                    'totalProjects'      => (int) $row['total_projects'],
                    'completedProjects'  => (int) $row['completed_projects']
                ];
                $workers->add(Worker::createPartial($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Searches for workers based on provided criteria and returns a WorkerContainer of matching results.
     *
     * This method allows searching for workers by keyword, project association, and worker status.
     * It supports full-text search on user fields, filtering by project ID (integer or UUID), and filtering by worker status.
     * Special handling is provided for the UNASSIGNED status, including exclusion of workers assigned to ongoing projects
     * and optionally excluding workers terminated from a specific project.
     * The method also supports pagination via limit and offset options.
     *
     * @param string|null $key Optional search keyword for full-text search on user fields (first_name, middleName, lastName, bio, email).
     * @param int|UUID|null $projectId Optional project identifier (integer ID or UUID) to filter workers by project association.
     * @param WorkerStatus|null $status Optional worker status to filter results (e.g., ASSIGNED, UNASSIGNED, TERMINATED).
     * @param array $options Optional associative array for additional options:
     *      - limit: int (default 10) Maximum number of results to return.
     *      - offset: int (default 0) Number of results to skip (for pagination).
     *      - excludeProjectTerminated: bool (optional) If true and status is UNASSIGNED, excludes workers terminated from the specified project.
     *
     * @return WorkerContainer|null A WorkerContainer with matching Worker instances, or null if no results found.
     *
     * @throws InvalidArgumentException If an invalid project ID is provided.
     * @throws DatabaseException If a database error occurs during the search.
     */
    public function search(
        string|null $key = '',
        array $options = [
            'projectId' => null,
            'status'    => null,

            'limit'     => 10,
            'offset'    => 0,
        ]
    ): WorkerContainer|null {
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
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
        ];

        try {
            $where = [];
            $params = [];

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
                    pw.status,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(jt.title) AS jobTitles
                FROM
                    `user` AS u
                LEFT JOIN
                    `project_worker` AS pw
                ON
                    u.id = pw.worker_id
                LEFT JOIN
                    `job_title` AS jt
                ON
                    u.id = jt.user_id";

            if (trimOrNull($key)) {
                $where[] =
                    "MATCH (
                        u.first_name, 
                        u.middle_name, 
                        u.last_name, 
                        u.bio, 
                        u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)";
                $params[':key'] = $key;
            }

            // Don't filter by project_id when searching for unassigned workers
            // The NOT EXISTS clause handles the assignment check globally
            if ($projectId && $status !== WorkerStatus::UNASSIGNED) {
                $where[] = \is_int($projectId)
                    ? "pw.project_id = :projectId"
                    : "pw.project_id = (SELECT id FROM `project` WHERE public_id = :projectId)";
                $params[':projectId'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }

            if ($status) {
                if ($status === WorkerStatus::UNASSIGNED) {
                    $params[':assignedStatus'] = WorkerStatus::ASSIGNED->value;
                    $params[':completedStatus'] = WorkStatus::COMPLETED->value;
                    $params[':cancelledStatus'] = WorkStatus::CANCELLED->value;

                    // Core rule: Include users with no project assignments OR users not assigned to ongoing projects
                    $where[] = "NOT EXISTS (
                        SELECT 1
                        FROM 
                            `project_worker` AS pw2
                        INNER JOIN 
                            `project` AS p2 
                        ON 
                            pw2.project_id = p2.id
                        WHERE 
                            pw2.worker_id = u.id
                        AND 
                            pw2.status = :assignedStatus
                        AND 
                            p2.status NOT IN (
                                :completedStatus, :cancelledStatus
                            )
                    )";

                    if ($projectId && ($options['excludeProjectTerminated'])) {
                        // Exclude workers terminated from this specific project
                        $where[] = "NOT EXISTS (
                            SELECT 1
                            FROM 
                                `project_worker` AS pw3
                            WHERE 
                                pw3.worker_id = u.id
                            AND 
                                pw3.project_id = " . (\is_int($projectId)
                            ? ":projectIdTermCheck"
                            : "(SELECT id 
                                    FROM 
                                        `project` 
                                    WHERE 
                                        public_id = :projectIdTermCheck
                                )") . "
                            AND 
                                pw3.status = :terminatedStatus
                        )";
                        $params[':terminatedStatus'] = WorkerStatus::TERMINATED->value;
                        $params[':projectIdTermCheck'] = \is_int($projectId)
                            ? $projectId
                            : UUID::toBinary($projectId);
                    }
                } else {
                    $where[] = "pw.status = :status";
                    $params[':status'] = $status->value;
                }
            }

            $where[] = "u.role = :role";
            $params[':role'] = Role::WORKER->value;

            $where[] = "u.confirmed_at IS NOT NULL AND u.deleted_at IS NULL";

            if (!empty($where)) {
                $query .= " WHERE " . implode(' AND ', $where);
            }
            $query .= " 
                GROUP BY 
                    u.id, pw.status
                ORDER BY 
                    u.last_name ASC  
                LIMIT "
                . \intval($paramOptions['limit']) . "
                OFFSET "
                . \intval($paramOptions['offset']);

            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$this->hasData($result)) return null;

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
     * Finds multiple Worker instances by their IDs, optionally filtered by project and including project/task history.
     *
     * This method retrieves worker data from the database, supporting both integer and UUID worker IDs.
     * It can also filter workers by a specific project and optionally include a history of projects and tasks
     * associated with each worker.
     *
     * - Throws InvalidArgumentException if workerIds array is empty or project_id is invalid.
     * - Supports both integer and UUID types for worker and project IDs.
     * - If $includeHistory is true, includes up to 10 recent projects and their associated tasks for each worker.
     * - Aggregates job titles, task counts, and project counts for each worker.
     *
     * @param array $workerIds Array of worker IDs (int or UUID) to search for.
     * @param int|UUID|null $projectId Optional project ID (int or UUID) to filter workers by project.
     * @param bool $includeHistory Whether to include project, phase, and task history for each worker.
     *
     * @throws InvalidArgumentException If workerIds is empty or project_id is invalid.
     * @throws DatabaseException If a database error occurs during query execution.
     *
     * @return WorkerContainer|null Container of Worker instances matching the criteria, or null if none found.
     */

    public function findById(
        int|UUID|array $workerId,
        array $options = [
            'projectId' => null,
        ]
    ): Worker|WorkerContainer|null {
        $isBatch = \is_array($workerId);
        $workerIds = $isBatch ? array_values($workerId) : [$workerId];
        if (empty($workerIds)) throw new InvalidArgumentException('Worker IDs array cannot be empty');

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');
            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException('Invalid project ID provided');
        }

        // Determine if workerIds are integers or UUIDs based on first element
        $firstIsInt = \is_int($workerIds[0]);
        foreach ($workerIds as $id) {
            if (!\is_int($id) && !($id instanceof UUID))
                throw new InvalidArgumentException('Worker ID must be an integer or UUID');

            if ($firstIsInt !== \is_int($id))
                throw new InvalidArgumentException('Worker IDs must be of the same type (all int or all UUID)');

            if (\is_int($id) && $id < 1)
                throw new InvalidArgumentException('Invalid worker ID provided');
        }

        try {
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

            $workerIdColumn = $firstIsInt ? "u.id" : "u.public_id";
            $where = "$workerIdColumn IN (" . implode(', ', $workerIdPlaceholders) . ")";

            if ($projectId) {
                $where .= " AND " . (\is_int($projectId) ? "p.id" : "p.public_id") . " = :projectId";
                $params[':projectId'] = ($projectId instanceof UUID)
                    ? UUID::toBinary($projectId)
                    : $projectId;
                $params[':projectId1'] = $params[':projectId'];
                $params[':projectId2'] = $params[':projectId'];
            }

            $query =
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
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    pw.default_rate,
                    pw.status,
                    GROUP_CONCAT(DISTINCT jt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `task_worker` AS tw
                        " . ($projectId ?
                    "INNER JOIN 
                            `task` AS t 
                        ON 
                            tw.task_id = t.id
                        INNER JOIN 
                            `phase` AS ph 
                        ON 
                            t.phase_id = ph.id
                        WHERE 
                            tw.worker_id = u.id 
                        AND 
                            ph.project_id = :projectId1"
                    : "WHERE 
                            tw.worker_id = u.id") . "
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
                        " . ($projectId ?
                    "INNER JOIN 
                                `phase` AS ph 
                        ON 
                            t.phase_id = ph.id"
                    : "") . "
                        WHERE 
                            tw.worker_id = u.id 
                        AND 
                            t.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            tw.status != '" . WorkerStatus::TERMINATED->value . "'"
                . ($projectId ? " AND ph.project_id = :projectId2" : "") . "
                    ) AS completed_tasks,
                    (
                        SELECT 
                            COUNT(*) 
                        FROM 
                            `project_worker` AS pw2 
                        WHERE 
                            pw2.worker_id = u.id
                    ) AS total_projects,
                    (
                        SELECT 
                            COUNT(*) 
                        FROM 
                            `project_worker` AS pw3
                        INNER JOIN 
                            `project` AS p2 
                        ON 
                            pw3.project_id = p2.id
                        WHERE 
                            pw3.worker_id = u.id 
                        AND 
                            p2.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            pw3.status != '" . WorkerStatus::TERMINATED->value . "'
                    ) AS completed_projects
                FROM
                    `user` AS u
                LEFT JOIN
                    `project_worker` AS pw
                ON
                    u.id = pw.worker_id
                INNER JOIN
                    `project` AS p
                ON
                    pw.project_id = p.id
                LEFT JOIN
                    `job_title` AS jt
                ON 
                    u.id = jt.user_id
                WHERE
                    $where
                GROUP BY
                    u.id, p.id
                ORDER BY
                    u.last_name ASC
            ";
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $results = $statement->fetchAll();

            if (!$this->hasData($results)) return null;

            $workers = new WorkerContainer();
            foreach ($results as $result) {
                $result['additional_info'] = [
                    'totalTasks'        => (int)$result['total_tasks'],
                    'completedTasks'    => (int)$result['completed_tasks'],
                    'totalProjects'     => (int)$result['total_projects'],
                    'completedProjects' => (int)$result['completed_projects'],
                ];
                $worker = Worker::createPartial($result);
                $workers->add($worker);
            }

            return $isBatch ? $workers : $workers->first();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds and retrieves all workers assigned to a specific project, including their job titles and project statistics.
     *
     * This method queries the database to fetch all users who are assigned as workers to the specified project by joining
     * the user, projectWorker, and project tables. It also LEFT JOINs jobTitle to aggregate job titles for each worker.
     *
     * For each worker, the following additional statistics are included:
     *   - totalProjects: The total number of projects the worker is assigned to (across all projects)
     *   - completedProjects: The number of projects the worker is assigned to that have status 'completed'
     *
     * The method supports both integer and UUID project IDs. The returned WorkerContainer contains Worker objects with
     * job titles and additionalInfo fields populated.
     *
     * @param int|UUID $projectId The project ID (int) or public UUID (UUID) to find workers for
     * @param array $options Optional settings:
     *      - limit: int (default: 10) Maximum number of workers to return
     *      - offset: int (default: 0) Number of workers to skip
     * @return WorkerContainer|null Container with Worker objects if workers are found, null if no workers are associated
     * @throws InvalidArgumentException If project_id is invalid
     * @throws DatabaseException If a database error occurs during the query execution
     *
     * SQL Details:
     * - Joins user, projectWorker, project, and userJobTitle tables
     * - Uses subqueries to count total and completed projects for each worker
     * - GROUP_CONCAT is used to aggregate job titles
     * - GROUP BY u.id ensures one row per worker
     */
    public function findByProjectId(
        int|UUID $projectId,
        array $options = [
            'limit' => 10,
            'offset' => 0
    ]): WorkerContainer|null {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID provided');

        try {
            $whereClause = (\is_int($projectId)
                ? "p.id"
                : "p.public_id ") . " = :id 
                AND pw.status != :unassignedStatus 
                AND pw.status != :terminatedStatus";

            $params = [
                ':id'               => ($projectId instanceof UUID) ? UUID::toBinary($projectId) : $projectId,
                ':unassignedStatus' => WorkerStatus::UNASSIGNED->value,
                ':terminatedStatus' => WorkerStatus::TERMINATED->value,
            ];

            $paramOptions = [
                'limit'     => $options['limit'] ?? 10,
                'offset'    => $options['offset'] ?? 0,
            ];

            return self::find($whereClause, $params, $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Determines if a worker is currently assigned to a project and not terminated.
     *
     * This method checks the `project_worker` table to verify if the specified worker is actively working on the given project.
     * It supports both integer IDs and UUIDs for project and worker identifiers. The method performs an INNER JOIN with the
     * `project` and `user` tables to ensure the existence and validity of the referenced entities. The worker is considered
     * active if their status is not equal to `TERMINATED`.
     *
     * @param int|UUID $workerId The worker identifier. Can be an integer ID or a UUID object.
     * @param int|UUID $projectId The project identifier. Can be an integer ID or a UUID object.
     *
     * @return bool Returns true if the worker is actively assigned to the project and not terminated, false otherwise.
     *
     * @throws InvalidArgumentException If an invalid project ID or worker ID is provided.
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    public function isWorkingOn(int|UUID $userId, int|UUID $projectId): bool
    {
        if (\is_int($projectId) && $projectId < 1) throw new InvalidArgumentException('Invalid project ID provided');
        if (\is_int($userId) && $userId < 1) throw new InvalidArgumentException('Invalid user ID provided');

        try {
            $query =
                "SELECT *
                FROM 
                    `project_worker` AS pw
                INNER JOIN 
                    `project` AS p 
                ON 
                    pw.project_id = p.id
                INNER JOIN 
                    `user` AS u
                ON 
                    pw.worker_id = u.id
                WHERE 
                    " . (\is_int($projectId) ? "p.id" : "p.public_id") . " = :projectId
                AND 
                    (
                        " . (\is_int($userId) ? "u.id" : "u.public_id") . " = :userId1
                    OR
                        p.manager_id = :userId2
                    )
                AND 
                    pw.status != :terminatedStatus
            ";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':projectId'        => ($projectId instanceof UUID)
                    ? UUID::toBinary($projectId)
                    : $projectId,
                ':userId1'           => ($userId instanceof UUID)
                    ? UUID::toBinary($userId)
                    : $userId,
                ':userId2'           => $userId,
                ':terminatedStatus' => WorkerStatus::TERMINATED->value
            ]);
            return $this->hasData($statement->fetchAll());
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves a paginated list of all workers.
     *
     * This method fetches a collection of workers from the data source, supporting pagination
     * through the use of offset and limit parameters. The results are ordered by creation date
     * in descending order.
     *
     * @param int $offset The number of records to skip before starting to collect the result set. Must be zero or positive.
     * @param int $limit The maximum number of records to return. Must be at least 1.
     *
     * @throws InvalidArgumentException If the offset is negative or the limit is less than 1.
     * @throws Exception If an error occurs during data retrieval.
     *
     * @return WorkerContainer|null A container with the retrieved workers, or null if no workers are found.
     */
    public function all(array $options = [
        'offset' => 0,
        'limit'  => 10
    ]): WorkerContainer|null {
        $offset = $options['offset'] ?? 0;
        if ($offset < 0) throw new InvalidArgumentException('Invalid offset value');

        $limit = $options['limit'] ?? 10;
        if ($limit < 1) throw new InvalidArgumentException('Invalid limit value');

        try {
            return $this->find('', [], [
                'offset'    => $offset,
                'limit'     => $limit,
                'orderBy'   => 'u.last_name ASC',
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates one or more project-worker assignments.
     *
     * Mirrors the PhaseModel::create() style:
     * - Validates projectId
     * - Normalizes a single Worker into WorkerContainer
     * - Prepares the INSERT once and executes it for each worker
     *
     * Note: This method uses the worker public UUID to resolve the internal user ID.
     * Status is set to WorkerStatus::ASSIGNED for backward compatibility with createMultiple().
     */
    public function create(
        int|UUID $projectId, 
        Worker|WorkerContainer $worker
    ): Worker|WorkerContainer {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID provided');

        // Allow passing a single Worker without wrapping
        $isBatch = $worker instanceof WorkerContainer;
        $workers = $isBatch ? $worker : new WorkerContainer([$worker]);
        if ($workers->count() === 0) throw new InvalidArgumentException('WorkerContainer cannot be empty');

        try {
            $insertQuery =
                "INSERT INTO `project_worker` (
                    project_id,
                    worker_id,
                    status,
                    default_rate
                ) VALUES (
                    (
                        SELECT id
                        FROM `project`
                        WHERE " . (\is_int($projectId) ? 'id' : 'public_id') . " = :projectId
                    ),
                    (
                        SELECT id
                        FROM `user`
                        WHERE public_id = :workerId
                    ),
                    :status,
                    :defaultRate
                ) ON DUPLICATE KEY UPDATE
                    status = VALUES(status)";

            $statement = $this->connection->prepare($insertQuery);
            foreach ($workers as &$worker) {
                $publicId = $worker->getPublicId();

                $statement->execute([
                    ':projectId'    => ($projectId instanceof UUID)
                        ? UUID::toBinary($projectId)
                        : $projectId,
                    ':workerId'     => UUID::toBinary($publicId),
                    ':status'       => WorkerStatus::ASSIGNED->value,
                    ':defaultRate'  => $worker->getDefaultRate(),
                ]);
            }

            return $isBatch ? $workers : $workers->first();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Persists a project-worker association.
     *
     * Validates and normalizes the provided $data array, then inserts or updates
     * the corresponding project-worker record in persistent storage. Handles
     * required field checks, basic type coercion, timestamp management and
     * conflict resolution (upsert behavior) as appropriate.
     *
     * @param array $data Data required to create a ProjectWorker instance. The expected
     *      structure and type of this data is not defined as the method is not implemented.
     *
     * @return bool Returns false as the method is not implemented.
     */
    public function save(int|UUID $projectId, array $workers): bool
    {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID provided');
        if (empty($workers)) throw new InvalidArgumentException('Worker array cannot be empty');

        // Allow passing a single worker update item without wrapping
        $isBatch = isAssociativeArray($workers) ? false : true;
        if (!$isBatch) $workers = [$workers];

        try {
            foreach ($workers as $item) {
                if (!\is_array($item))
                    throw new InvalidArgumentException('Each worker update item must be an array');

                if (!isset($item['id']) && !isset($item['publicId']) && !isset($item['workerId']))
                    throw new InvalidArgumentException('Worker ID is required');

                // Determine worker ID (int or UUID)
                $rawId = $item['id'] ?? $item['workerId'] ?? null;
                $rawPublicId = $item['publicId'] ?? null;
                $id = ($rawId !== null)
                    ? (int) $rawId
                    : ($rawPublicId instanceof UUID
                        ? $rawPublicId
                        : UUID::fromString($rawPublicId));
                if (\is_int($id) && $id < 1)
                    throw new InvalidArgumentException('Invalid worker ID provided');

                $updateFields = [];
                $params = [];
                $whereParts = [];

                // Build WHERE clause for project_id and worker_id
                $whereParts[] = (\is_int($projectId))
                    ? 'project_id = :projectId'
                    : 'project_id = (SELECT id FROM `project` WHERE public_id = :projectId)';
                $params[':projectId'] = (\is_int($projectId))
                    ? $projectId
                    : UUID::toBinary($projectId);

                $whereParts[] = (\is_int($id))
                    ? 'worker_id = :id'
                    : 'worker_id = (SELECT id FROM `user` WHERE public_id = :id)';
                $params[':id'] = (\is_int($id))
                    ? $id
                    : UUID::toBinary($id);

                if (isset($item['defaultRate']) || isset($item['default_rate'])) {
                    $updateFields[] = 'default_rate = :defaultRate';
                    $params[':defaultRate'] = $item['defaultRate'] ?? $item['default_rate'];
                }

                if (isset($item['status'])) {
                    $updateFields[] = 'status = :status';
                    $params[':status'] = ($item['status'] instanceof WorkerStatus)
                        ? $item['status']->value
                        : $item['status'];
                }

                if (empty($updateFields)) continue;

                $where = implode(' AND ', $whereParts);
                $query =
                    'UPDATE 
                        `project_worker` 
                    SET 
                        ' . implode(', ', $updateFields) . ' 
                    WHERE 
                        ' . $where;
                $statement = $this->connection->prepare($query);
                $statement->execute($params);
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Deletes a worker associated from a given project.
     *
     * This method accepts either internal numeric IDs or public identifiers (UUIDs/binary/string)
     * for both project and worker. It builds a DELETE query that either uses the provided numeric
     * IDs directly or resolves public identifiers to internal IDs via subqueries against the
     * `project` and `user` tables. UUID instances are converted to binary before binding.
     *
     * Validations performed:
     * - Ensures project_id and worker_id are present.
     * - Ensures numeric IDs are positive integers when provided as ints.
     *
     * @param array $data Associative array containing identifiers with the following keys:
     *      - projectId: int|string|UUID|binary
     *          Either the internal numeric project ID (int) or a public identifier (UUID instance,
     *          binary representation, or public_id string). If an int is provided it is used directly;
     *          otherwise the project internal ID is resolved via a subquery on `project.public_id`.
     *      - workerId: int|string|UUID|binary
     *          Either the internal numeric worker (user) ID (int) or a public identifier (UUID instance,
     *          binary representation, or public_id string). If an int is provided it is used directly;
     *          otherwise the worker internal ID is resolved via a subquery on `user.public_id`.
     *
     * @return bool True when the deletion query executed successfully.
     *
     * @throws InvalidArgumentException If project_id or worker_id is missing or an invalid integer is provided.
     * @throws DatabaseException If a PDO error occurs while preparing or executing the statement.
     */
    public function delete(mixed $data): bool
    {
        if (!isset($data['projectId'])) throw new InvalidArgumentException('Project ID is required');
        if (\is_int($data['projectId']) && $data['projectId'] < 1)
            throw new InvalidArgumentException('Invalid project ID provided');
        if (!isset($data['workerId'])) throw new InvalidArgumentException('Worker ID is required');
        if (\is_int($data['workerId']) && $data['workerId'] < 1)
            throw new InvalidArgumentException('Invalid worker ID provided');

        try {
            $query =
                "DELETE FROM
                    `project_worker`
                WHERE 
                    project_id = " . (\is_int($data['projectId']) ? ':projectId' : '(
                        SELECT 
                            id 
                        FROM 
                            `project` 
                        WHERE 
                            public_id = :projectId) ') . "
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
                ':projectId'    => ($data['projectId'] instanceof UUID)
                    ? UUID::toBinary($data['projectId'])
                    : $data['projectId'],
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
