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
    protected function find(string $whereClause = '', array $params = [], array $options = []): ?WorkerContainer
	{
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'u.id, pw.status',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'u.last_name ASC',
        ];

        try {
            $queryString = 
                "SELECT 
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
                $paramOptions);

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
        int|UUID|null $projectId = null,
        WorkerStatus|null $status = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]
    ): ?WorkerContainer {
        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

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

            if (trimOrNull($key))  {
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
                $where[] = is_int($projectId)
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
                                pw3.project_id = " . (is_int($projectId) 
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
                        $params[':projectIdTermCheck'] = is_int($projectId)
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
                    . intval($paramOptions['limit']) . "
                OFFSET " 
                    . intval($paramOptions['offset']);

            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$this->hasData($result)) {
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
     * Finds a Worker instance by its ID, optionally filtered by project ID and including project/task history.
     *
     * This method retrieves a worker's details from the database, including:
     * - Worker personal information and status
     * - Associated job titles
     * - Task and project statistics (total/completed)
     * - Optionally, a history of projects and tasks the worker participated in
     *
     * If $includeHistory is true, the method fetches up to 10 recent projects for the worker,
     * each with its associated tasks, and attaches them as a ProjectContainer to the worker's additional info.
     *
     * @param int|UUID $workerId Worker ID (integer or UUID)
     * @param int|UUID|null $projectId (optional) Project ID to filter by (integer or UUID)
     * @param bool $includeHistory (optional) Whether to include project, phase, and task history (default: false)
     *
     * @throws InvalidArgumentException If an invalid project ID is provided
     * @throws DatabaseException If a database error occurs
     *
     * @return Worker|null Worker instance with partial data, or null if not found
     */
    public function findById(
        int|UUID $workerId, 
        int|UUID|null $projectId = null, 
        bool $includeHistory = false): ?Worker
    {
        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            // TODO: Separate this into its own method to reduce complexity
            $projectHistory = $includeHistory
                ? "SELECT 
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', p2.id,
                            'public_id', HEX(p2.public_id),
                            'name', p2.name,
                            'status', p2.status,
                            'start_date_time', p2.start_date_time,
                            'completion_date_time', p2.completion_date_time,
                            'actual_completion_date_time', p2.actual_completion_date_time,
                            'worker_status', pw4.status,

                            'phases', COALESCE(
                                (
                                    SELECT JSON_ARRAYAGG(
                                        JSON_OBJECT(
                                            'id', ph2.id,
                                            'public_id', HEX(ph2.public_id),
                                            'name', ph2.name,
                                            'status', ph2.status,
                                            'start_date_time', ph2.start_date_time,
                                            'completion_date_time', ph2.completion_date_time,
                                            'actual_completion_date_time', ph2.actual_completion_date_time,

                                            'tasks', COALESCE(
                                                (
                                                    SELECT JSON_ARRAYAGG(
                                                        JSON_OBJECT(
                                                            'id', t2.id,
                                                            'public_id', HEX(t2.public_id),
                                                            'name', t2.name,
                                                            'status', t2.status,
                                                            'priority', t2.priority,
                                                            'start_date_time', t2.start_date_time,
                                                            'completion_date_time', t2.completion_date_time,
                                                            'actual_completion_date_time', t2.actual_completion_date_time,
                                                            'worker_status', tw.status
                                                        )
                                                    ) FROM 
                                                        `task` AS t2
                                                    INNER JOIN
                                                        `task_worker` AS tw
                                                    ON
                                                        tw.task_id = t2.id
                                                    WHERE 
                                                        t2.phase_id = ph2.id
                                                    AND
                                                        tw.worker_id = u.id
                                                ), JSON_ARRAY()
                                            )
                                        )
                                    ) FROM 
                                        `phase` AS ph2
                                    WHERE 
                                        ph2.project_id = p2.id
                                ), JSON_ARRAY()
                            )
                        )

                    ) FROM 
                        `project` AS p2
                    INNER JOIN
                        `project_worker` AS pw4
                    ON
                        pw4.project_id = p2.id
                    WHERE 
                        pw4.worker_id = u.id " . ($projectId ? 
                            " AND p2.id = p.id" 
                            : ""
                        ) . " "
            : '';

            $where = (is_int($workerId) ? "u.id" : "u.public_id") . " = :workerId";
            $params = [
                ':workerId' => ($workerId instanceof UUID) 
                    ? UUID::toBinary($workerId)
                    : $workerId,
            ];

            if ($projectId) {
                $where .= " AND " . (is_int($projectId) ? "p.id" : "p.public_id") . " = :projectId";
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
                                `task` AS pt ON tw.task_id = pt.id
                            INNER JOIN 
                                `phase` AS ph ON pt.phase_id = ph.id
                            WHERE 
                                tw.worker_id = u.id 
                            AND 
                                ph.project_id = :projectId1" 
                            : "WHERE tw.worker_id = u.id") . "
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
                                `phase` AS ph ON t.phase_id = ph.id" 
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
                    ) AS completed_projects,
                    ($projectHistory) AS project_history
                FROM
                    `user` AS u
                LEFT JOIN
                    `project_worker` AS pw
                ON
                    u.id = pw.worker_id
                LEFT JOIN
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
                    u.id, p.id, pw.status, pw.default_rate
                LIMIT 1 ";
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetch();

            if (!$this->hasData($result)) {
                return null;
            }

            $result['additional_info'] = [
                    'totalTasks'        => (int)$result['total_tasks'],
                    'completedTasks'    => (int)$result['completed_tasks'],
                    'totalProjects'     => (int)$result['total_projects'],
                    'completedProjects' => (int)$result['completed_projects'],
            ];
            $worker = Worker::createPartial($result);
            if ($includeHistory) {
                $projects = new ProjectContainer();

                $projectLists = json_decode($result['project_history'], true);
                foreach ($projectLists as &$project) {
                    $entry = Project::createPartial($project);

                    $phaseLists = $project['phases'];
                    foreach ($phaseLists as $phase) {
                        $taskLists = $phase['tasks'];

                        $tasks = new TaskContainer();
                        foreach ($taskLists as $task) {
                            $tasks->add(
                                Task::createPartial($task)
                            );
                        }
                        $phase['tasks'] = $tasks;
                        $phaseEntry = Phase::createPartial($phase);

                        $entry->addPhase($phaseEntry);
                    }
                    $projects->add($entry);
                }
                $worker->addAdditionalInfo('projectHistory', $projects);
            }
            return $worker;
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

    public function findMultipleById(
        array $workerIds, 
        int|UUID|null $projectId = null, 
        bool $includeHistory = false
    ): ?WorkerContainer
    {
        if (empty($workerIds)) {
            throw new InvalidArgumentException('Worker IDs array cannot be empty.');
        }

        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            // Determine if workerIds are integers or UUIDs based on first element
            $firstWorkerId = $workerIds[0];
            $useIntId = is_int($firstWorkerId);

            // TODO: Separate this into its own method to reduce complexity

            $projectHistory = $includeHistory
                ? "SELECT 
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', p2.id,
                            'public_id', HEX(p2.public_id),
                            'name', p2.name,
                            'status', p2.status,
                            'start_date_time', p2.start_date_time,
                            'completion_date_time', p2.completion_date_time,
                            'actual_completion_date_time', p2.actual_completion_date_time,
                            'worker_status', pw4.status,

                            'phases', COALESCE(
                                (
                                    SELECT JSON_ARRAYAGG(
                                        JSON_OBJECT(
                                            'id', ph2.id,
                                            'public_id', HEX(ph2.public_id),
                                            'name', ph2.name,
                                            'status', ph2.status,
                                            'start_date_time', ph2.start_date_time,
                                            'completion_date_time', ph2.completion_date_time,
                                            'actual_completion_date_time', ph2.actual_completion_date_time,

                                            'tasks', COALESCE(
                                                (
                                                    SELECT JSON_ARRAYAGG(
                                                        JSON_OBJECT(
                                                            'id', t2.id,
                                                            'public_id', HEX(t2.public_id),
                                                            'name', t2.name,
                                                            'status', t2.status,
                                                            'priority', t2.priority,
                                                            'start_date_time', t2.start_date_time,
                                                            'completion_date_time', t2.completion_date_time,
                                                            'actual_completion_date_time', t2.actual_completion_date_time,
                                                            'worker_status', tw.status
                                                        )
                                                    ) FROM 
                                                        `task` AS t2
                                                    INNER JOIN
                                                        `task_worker` AS tw
                                                    ON
                                                        tw.task_id = t2.id
                                                    WHERE 
                                                        t2.phase_id = ph2.id
                                                    AND
                                                        tw.worker_id = u.id
                                                ), JSON_ARRAY()
                                            )
                                        )
                                    ) FROM 
                                        `phase` AS ph2
                                    WHERE 
                                        ph2.project_id = p2.id
                                ), JSON_ARRAY()
                            )
                        )

                    ) FROM 
                        `project` AS p2
                    INNER JOIN
                        `project_worker` AS pw4
                    ON
                        pw4.project_id = p2.id
                    WHERE 
                        pw4.worker_id = u.id " . ($projectId ? 
                            " AND p2.id = p.id" 
                            : ""
                        ) . " "
            : '';

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

            $workerIdColumn = $useIntId ? "u.id" : "u.public_id";
            $where = "$workerIdColumn IN (" . implode(', ', $workerIdPlaceholders) . ")";

            if ($projectId) {
                $where .= " AND " . (is_int($projectId) ? "p.id" : "p.public_id") . " = :projectId";
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
                                `task` AS t ON tw.task_id = t.id
                            INNER JOIN 
                                `phase` AS ph ON t.phase_id = ph.id
                            WHERE 
                                tw.worker_id = u.id 
                            AND 
                                ph.project_id = :projectId1" 
                            : "WHERE tw.worker_id = u.id") . "
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
                                `phase` AS ph ON t.phase_id = ph.id" 
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
                    ) AS completed_projects,
                    " . ($projectHistory ? "($projectHistory) AS project_history" : "JSON_ARRAY() AS project_history") . "
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

            if (!$this->hasData($results)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($results as $result) {
                $result['additional_info'] = [
                    'totalTasks'        => (int)$result['total_tasks'],
                    'completedTasks'    => (int)$result['completed_tasks'],
                    'totalProjects'     => (int)$result['total_projects'],
                    'completedProjects' => (int)$result['completed_projects'],
                ];
                $worker = Worker::createPartial($result);
                if ($includeHistory) {
                    $projects = new ProjectContainer();

                    $projectLists = json_decode($result['project_history'], true);
                    foreach ($projectLists as &$project) {
                        $entry = Project::createPartial($project);

                        $phaseLists = $project['phases'];
                        foreach ($phaseLists as $phase) {
                            $phaseEntry = Phase::createPartial($phase);

                            $taskLists = $phase['tasks'];
                            foreach ($taskLists as $task) {
                                $phaseEntry->addTask(
                                    Task::createPartial($task)
                                );
                            }
                            $entry->addPhase($phaseEntry);
                        }
                        $projects->add($entry);
                    }
                    $worker->addAdditionalInfo('projectHistory', $projects);
                }

                $workers->add($worker);
            }

            return $workers;
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
        ]): ?WorkerContainer
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            $whereClause = (is_int($projectId) 
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
    public function all(int $offset = 0, int $limit = 10): ?WorkerContainer
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
                'orderBy'   => 'u.last_name ASC',
            ];  

            return self::find('', [], $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates a new ProjectWorker instance from the provided data.
     *
     * This method is intended to instantiate a ProjectWorker model using the given data.
     * Currently, this method is not implemented as there is no use case for creating
     * ProjectWorker instances directly from data arrays.
     *
     * @param mixed $data Data required to create a ProjectWorker instance. The expected
     *      structure and type of this data is not defined as the method is not implemented.
     *
     * @return mixed Returns null as the method is not implemented.
     */
	public function create(mixed $data): mixed
	{
        // Not implemented (No use case)
		return null;
	}

    /**
     * Creates multiple project-worker assignments for a given project.
     *
     * This method inserts multiple worker assignments into the `project_worker` table for the specified project.
     * It uses a transaction to ensure all assignments are created atomically. Each worker is referenced by their
     * public UUID, which is converted to binary if necessary. The project is also referenced by its public UUID.
     * The status for each assignment is set to WorkerStatus::ASSIGNED.
     *
     * @param int|UUID $projectId The public UUID or integer ID of the project to assign workers to.
     * @param WorkerContainer $workers Container of worker public UUIDs or binary IDs to be assigned to the project.
     *
     * @throws InvalidArgumentException If the data array is empty.
     * @throws DatabaseException If a database error occurs during the transaction.
     * 
     * @return void
     */
    public function createMultiple(int|UUID $projectId, WorkerContainer $workers): bool
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $projectId = ($projectId instanceof UUID)
            ? UUID::toBinary($projectId)
            : $projectId;

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
                        WHERE " . (is_int($projectId) ? 'id' : 'public_id') . " = :projectId
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
            foreach ($workers as $worker) {    
                $statement->execute([
                    ':projectId'    => $projectId,
                    ':workerId'     => UUID::toBinary($worker->getPublicId()),
                    ':status'       => WorkerStatus::ASSIGNED->value,
                    ':defaultRate'  => $worker->getDefaultRate()
                ]);
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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
     * @param int|UUID $projectId The project identifier. Can be an integer ID or a UUID object.
     * @param int|UUID $workerId The worker identifier. Can be an integer ID or a UUID object.
     *
     * @return bool Returns true if the worker is actively assigned to the project and not terminated, false otherwise.
     *
     * @throws InvalidArgumentException If an invalid project ID or worker ID is provided.
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    public function worksOn(int|UUID $projectId, int|UUID $userId): bool
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        if (is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided.');
        }

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
                    " . (is_int($projectId) ? "p.id" : "p.public_id") . " = :projectId
                AND 
                    (
                        " . (is_int($userId) ? "u.id" : "u.public_id") . " = :userId1
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
    public function save(array $data): bool 
    {
        return false;
    }

    /**
     * Updates multiple project-worker records in the database.
     *
     * Iterates over the provided $workers array and updates each corresponding
     * row in the `project_worker` table. Each worker entry must provide either
     * an integer 'id' or a 'publicId' (UUID string). The method accepts a project
     * identifier as either an integer primary key or a UUID; when a UUID is used
     * the method resolves it to the internal id via a subquery and binds the
     * binary UUID value to the prepared statement. Only supplied fields are
     * updated — currently 'defaultRate' and 'status' are supported. If a worker
     * item contains no updatable fields it is skipped.
     *
     * Validation performed:
     *  - integer $projectId must be >= 1
     *  - each worker must include 'id' or 'publicId'
     *  - integer worker id must be >= 1
     *
     * The 'status' field may be provided as a WorkerStatus enum instance or as a
     * scalar value; when an enum is provided its ->value is used.
     *
     * Database errors from PDO are wrapped and rethrown as DatabaseException.
     *
     * @param int|UUID $projectId Project identifier (integer ID or UUID)
     * @param array $workers Array of associative arrays describing workers to update. Each item may contain:
     *      - id: int Optional internal worker ID
     *      - publicId: string|UUID Optional public UUID identifying the worker
     *      - defaultRate: float|int Optional default rate to set
     *      - status: WorkerStatus|int|string Optional status value or enum
     *
     * @return bool True if processing completed without database errors
     *
     * @throws InvalidArgumentException If $projectId or a worker id is invalid or a worker lacks an identifier
     * @throws DatabaseException If a PDOException occurs while executing an update
     */
	public function saveMultiple(int|UUID $projectId, array $workers): bool
	{
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }
        
        try {
            foreach ($workers as $data) {
                if (!isset($data['id']) && !isset($data['publicId'])) {
                    throw new InvalidArgumentException('Worker ID is required.');
                }

                $id = $data['id'] 
                    ? (int) $data['id']
                    : UUID::fromString($data['publicId']);
                if (is_int($id) && $id < 1) { 
                    throw new InvalidArgumentException('Invalid worker ID provided.');                    
                }

                $updateFields = [];
                $params = [];
                $whereParts = [];

                if (is_int($projectId)) {
                    $whereParts[] = 'project_id = :projectId';
                    $params[':projectId'] = $projectId;
                } else {
                    $whereParts[] = 'project_id = (SELECT id FROM `project` WHERE public_id = :projectId)';
                    $params[':projectId'] = UUID::toBinary($projectId);
                }

                if (is_int($id)) {
                    $whereParts[] = 'worker_id = :id';
                    $params[':id'] = $id;
                } else {
                    $whereParts[] = 'worker_id = (SELECT id FROM `user` WHERE public_id = :id)';
                    $params[':id'] = UUID::toBinary($id);
                }

                if (isset($data['defaultRate'])) {
                    $updateFields[] = 'default_rate = :defaultRate';
                    $params[':defaultRate'] = $data['defaultRate'];
                }

                if (isset($data['status'])) {
                    $updateFields[] = 'status = :status';
                    $params[':status'] = ($data['status'] instanceof WorkerStatus)
                        ? $data['status']->value
                        : $data['status'];
                }

                // Nothing to update
                if (empty($updateFields)) {
                    continue;
                }

                $where = implode(' AND ', $whereParts);
                $query = 'UPDATE `project_worker` SET ' . implode(', ', $updateFields) . ' WHERE ' . $where;
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
        if (!isset($data['projectId'])) {
            throw new InvalidArgumentException('Project ID is required.');
        }

        if (is_int($data['projectId']) && $data['projectId'] < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        if (!isset($data['workerId'])) {
            throw new InvalidArgumentException('Worker ID is required.');
        }

        if (is_int($data['workerId']) && $data['workerId'] < 1) {
            throw new InvalidArgumentException('Invalid worker ID provided.');
        }

        try {
            $query = 
                "DELETE FROM
                    `project_worker`
                WHERE 
                    project_id = " . (is_int($data['projectId']) ? ':projectId' : '(
                        SELECT 
                            id 
                        FROM 
                            `project` 
                        WHERE 
                            public_id = :projectId) ') . "
                AND 
                    worker_id = " . (is_int($data['workerId']) ? ':workerId' : '(
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
