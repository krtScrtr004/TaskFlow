<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\JobTitleContainer;
use App\Container\ProjectContainer;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Dependent\Phase;
use App\Dependent\Worker;
use App\Entity\Project;
use App\Entity\Task;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Enumeration\TaskPriority;
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
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?WorkerContainer
	{
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'p.id',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'u.last_name ASC',
        ];

		$instance = new self();
        try {
            $queryString = "
                SELECT 
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
                    GROUP_CONCAT(DISTINCT ujt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        WHERE 
                            ptw.worker_id = u.id
                    ) AS total_tasks,
                    (
                        SELECT COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        INNER JOIN 
                            phase_task AS t 
                        ON 
                            ptw.task_id = t.id
                        WHERE 
                            ptw.worker_id = u.id
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
                    `user_job_title` AS ujt
                ON 
                    u.id = ujt.user_id
            ";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause), 
                $paramOptions);

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
                    'total_tasks'        => (int)$row['total_tasks'],
                    'completedTasks'     => (int)$row['completed_tasks'],
                    'totalProjects'      => (int)$row['total_projects'],
                    'completedProjects'  => (int)$row['completed_projects']
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
    public static function search(
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
            $instance = new self();

            $where = [];
            $params = [];

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
                    pw.status,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(ujt.title) AS jobTitles
                FROM
                    `user` AS u
                LEFT JOIN
                    `project_worker` AS pw
                ON
                    u.id = pw.worker_id
                LEFT JOIN
                    `user_job_title` AS ujt
                ON
                    u.id = ujt.user_id
            ";

            if (trimOrNull($key))  {
                $where[] = "
                    MATCH(u.first_name, u.middle_name, u.last_name, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)
                ";
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
                    u.id 
                ORDER BY 
                    u.last_name ASC  
                LIMIT " 
                    . intval($paramOptions['limit']) . "
                OFFSET " 
                    . intval($paramOptions['offset']);

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
    public static function findById(
        int|UUID $workerId, 
        int|UUID|null $projectId = null, 
        bool $includeHistory = false): ?Worker
    {
        if ($projectId && is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $instance = new self();
        try {
            $projectHistory = $includeHistory ?
                ", COALESCE(
                    (
                        SELECT CONCAT('[', GROUP_CONCAT(
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
                                        SELECT CONCAT('[', GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'id', pp2.id,
                                                'public_id', HEX(pp2.public_id),
                                                'name', pp2.name,
                                                'status', pp2.status,
                                                'start_date_time', pp2.start_date_time,
                                                'completion_date_time', pp2.completion_date_time,
                                                'actual_completion_date_time', pp2.actual_completion_date_time,
                                                'tasks', COALESCE(
                                                    (
                                                        SELECT CONCAT('[', GROUP_CONCAT(
                                                            JSON_OBJECT(
                                                                'id', pt2.id,
                                                                'public_id', HEX(pt2.public_id),
                                                                'name', pt2.name,
                                                                'status', pt2.status,
                                                                'priority', pt2.priority,
                                                                'start_date_time', pt2.start_date_time,
                                                                'completion_date_time', pt2.completion_date_time,
                                                                'actual_completion_date_time', pt2.actual_completion_date_time,
                                                                'worker_status', ptw.status
                                                            ) ORDER BY pt2.start_date_time ASC SEPARATOR ','
                                                        ), ']')
                                                        FROM 
                                                            `phase_task` AS pt2
                                                        INNER JOIN
                                                            `phase_task_worker` AS ptw
                                                        ON
                                                            ptw.task_id = pt2.id
                                                        WHERE 
                                                            pt2.phase_id = pp2.id
                                                        AND
                                                            ptw.worker_id = u.id
                                                    ), 
                                                    '[]'
                                                )
                                            ) ORDER BY pp2.start_date_time ASC SEPARATOR ','
                                        ), ']')
                                        FROM 
                                            `project_phase` AS pp2
                                        WHERE 
                                            pp2.project_id = p2.id
                                    ), 
                                    '[]'
                                )
                            ) ORDER BY p2.start_date_time ASC SEPARATOR ','
                        ), ']' )
                        FROM 
                            `project` AS p2
                        INNER JOIN
                            `project_worker` AS pw4
                        ON
                            pw4.project_id = p2.id
                        WHERE 
                            pw4.worker_id = u.id " . ($projectId ? 
                                " AND p2.id = p.id" 
                                : ""
                            ) . "
                    ), 
                    '[]'
                ) AS project_history"
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

            $query = "
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
                    pw.status,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(DISTINCT ujt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        " . ($projectId ? 
                            "INNER JOIN 
                                `phase_task` AS pt ON ptw.task_id = pt.id
                            INNER JOIN 
                                `project_phase` AS pp ON pt.phase_id = pp.id
                            WHERE 
                                ptw.worker_id = u.id 
                            AND 
                                pp.project_id = :projectId1" 
                            : "WHERE ptw.worker_id = u.id") . "
                    ) AS total_tasks,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        INNER JOIN 
                            `phase_task` AS t 
                        ON 
                            ptw.task_id = t.id
                        " . ($projectId ? 
                            "INNER JOIN 
                                `project_phase` AS pp ON t.phase_id = pp.id" 
                            : "") . "
                        WHERE 
                            ptw.worker_id = u.id 
                        AND 
                            t.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            ptw.status != '" . WorkerStatus::TERMINATED->value . "'"
                        . ($projectId ? " AND pp.project_id = :projectId2" : "") . "
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
                    $projectHistory
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
                    `user_job_title` AS ujt
                ON 
                    u.id = ujt.user_id
                WHERE
                    $where
                GROUP BY
                    u.id
                LIMIT 1 
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetch();

            if (!$instance->hasData($result)) {
                return null;
            }

            $worker = Worker::createPartial([
                'id'                    => $result['id'],
                'publicId'              => $result['public_id'],
                'firstName'             => $result['first_name'],
                'middleName'            => $result['middle_name'],
                'lastName'              => $result['last_name'],
                'bio'                   => $result['bio'],
                'gender'                => Gender::from($result['gender']),
                'email'                 => $result['email'],
                'contactNumber'         => $result['contact_number'],
                'profileLink'           => $result['profile_link'],
                'status'                => WorkerStatus::from($result['status']),
                'jobTitles'             => new JobTitleContainer(explode(',', $result['job_titles'] ?? '')),
                'additionalInfo'        => [
                    'totalTasks'        => (int)$result['total_tasks'],
                    'completedTasks'    => (int)$result['completed_tasks'],
                    'totalProjects'     => (int)$result['total_projects'],
                    'completedProjects' => (int)$result['completed_projects'],
                ],
                'createdAt'             => $result['created_at'],
                'confirmedAt'           => $result['confirmed_at'],
                'deletedAt'             => $result['deleted_at']
            ]);
            if ($includeHistory) {
                $projects = new ProjectContainer();

                $projectLists = json_decode($result['project_history'], true);
                foreach ($projectLists as &$project) {
                    $entry = Project::createPartial([
                        'id'                        => $project['id'],
                        'publicId'                  => UUID::fromHex($project['public_id']),
                        'name'                      => $project['name'],
                        'status'                    => WorkStatus::from($project['status']),
                        'startDateTime'             => $project['start_date_time'],
                        'completionDateTime'        => $project['completion_date_time'],
                        'actualCompletionDateTime'  => $project['actual_completion_date_time'],
                        'additionalInfo'            => [
                            'workerStatus'          => WorkerStatus::from($project['worker_status']),
                        ]
                    ]);

                    $phaseLists = json_decode($project['phases'], true);
                    foreach ($phaseLists as $phase) {
                        $phaseEntry = Phase::createPartial([
                            'id'                        => $phase['id'],
                            'publicId'                  => UUID::fromHex($phase['public_id']),
                            'name'                      => $phase['name'],
                            'status'                    => WorkStatus::from($phase['status']),
                            'startDateTime'             => $phase['start_date_time'],
                            'completionDateTime'        => $phase['completion_date_time'],
                            'actualCompletionDateTime'  => $phase['actual_completion_date_time'],
                        ]);

                        $taskLists = json_decode($phase['tasks'], true);
                        foreach ($taskLists as $task) {
                            $phaseEntry->addTask(
                                Task::createPartial([
                                    'id'                        => $task['id'],
                                    'publicId'                  => UUID::fromHex($task['public_id']),
                                    'name'                      => $task['name'],
                                    'status'                    => WorkStatus::from($task['status']),
                                    'priority'                  => TaskPriority::from($task['priority']),
                                    'startDateTime'             => $task['start_date_time'],
                                    'completionDateTime'        => $task['completion_date_time'],
                                    'actualCompletionDateTime'  => $task['actual_completion_date_time'],
                                    'additionalInfo'            => [
                                        'workerStatus'          => WorkerStatus::from($task['worker_status']),
                                    ]
                                ])
                            );
                        }
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

    public static function findMultipleById(
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

        $instance = new self();
        try {
            // Determine if workerIds are integers or UUIDs based on first element
            $firstWorkerId = $workerIds[0];
            $useIntId = is_int($firstWorkerId);

            $projectHistory = $includeHistory ?
                ", COALESCE(
                    (
                        SELECT CONCAT('[', GROUP_CONCAT(
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
                                        SELECT CONCAT('[', GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'id', pp2.id,
                                                'public_id', HEX(pp2.public_id),
                                                'name', pp2.name,
                                                'status', pp2.status,
                                                'start_date_time', pp2.start_date_time,
                                                'completion_date_time', pp2.completion_date_time,
                                                'actual_completion_date_time', pp2.actual_completion_date_time,
                                                'tasks', COALESCE(
                                                    (
                                                        SELECT CONCAT('[', GROUP_CONCAT(
                                                            JSON_OBJECT(
                                                                'id', pt2.id,
                                                                'publicId', HEX(pt2.public_id),
                                                                'name', pt2.name,
                                                                'status', pt2.status,
                                                                'priority', pt2.priority,
                                                                'start_date_time', pt2.start_date_time,
                                                                'completion_date_time', pt2.completion_date_time,
                                                                'actual_completion_date_time', pt2.actual_completion_date_time,
                                                                'worker_status', ptw.status
                                                            ) ORDER BY pt2.start_date_time ASC SEPARATOR ','
                                                        ), ']')
                                                        FROM 
                                                            `phase_task` AS pt2
                                                        INNER JOIN
                                                            `phase_task_worker` AS ptw
                                                        ON
                                                            ptw.task_id = pt2.id
                                                        WHERE 
                                                            pt2.phase_id = pp2.id
                                                        AND
                                                            ptw.worker_id = u.id
                                                    ), 
                                                    '[]'
                                                )
                                            ) ORDER BY pp2.start_date_time ASC SEPARATOR ','
                                        ), ']')
                                        FROM 
                                            `project_phase` AS pp2
                                        WHERE 
                                            pp2.project_id = p2.id
                                    ), 
                                    '[]'
                                )
                            ) ORDER BY p2.start_date_time ASC SEPARATOR ','
                        ), ']' )
                        FROM 
                            `project` AS p2
                        INNER JOIN
                            `project_worker` AS pw4
                        ON
                            pw4.project_id = p2.id
                        WHERE 
                            pw4.worker_id = u.id " . ($projectId ? 
                                " AND p2.id = p.id" 
                                : ""
                            ) . "
                    ), 
                    '[]'
                ) AS project_history"
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

            $query = "
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
                    pw.status,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(DISTINCT ujt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        " . ($projectId ? 
                            "INNER JOIN 
                                `phase_task` AS pt ON ptw.task_id = pt.id
                            INNER JOIN 
                                `project_phase` AS pp ON pt.phase_id = pp.id
                            WHERE 
                                ptw.worker_id = u.id 
                            AND 
                                pp.project_id = :projectId1" 
                            : "WHERE ptw.worker_id = u.id") . "
                    ) AS total_tasks,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `phase_task_worker` AS ptw
                        INNER JOIN 
                            `phase_task` AS t 
                        ON 
                            ptw.task_id = t.id
                        " . ($projectId ? 
                            "INNER JOIN 
                                `project_phase` AS pp ON t.phase_id = pp.id" 
                            : "") . "
                        WHERE 
                            ptw.worker_id = u.id 
                        AND 
                            t.status = '" . WorkStatus::COMPLETED->value . "'
                        AND 
                            ptw.status != '" . WorkerStatus::TERMINATED->value . "'"
                        . ($projectId ? " AND pp.project_id = :projectId2" : "") . "
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
                    $projectHistory
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
                    `user_job_title` AS ujt
                ON 
                    u.id = ujt.user_id
                WHERE
                    $where
                GROUP BY
                    u.id
                ORDER BY
                    u.last_name ASC
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $results = $statement->fetchAll();

            if (!$instance->hasData($results)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($results as $result) {
                $worker = Worker::createPartial([
                    'id'                    => $result['id'],
                    'publicId'              => $result['public_id'],
                    'firstName'             => $result['firstN_name'],
                    'middleName'            => $result['middle_name'],
                    'lastName'              => $result['last_name'],
                    'bio'                   => $result['bio'],
                    'gender'                => Gender::from($result['gender']),
                    'email'                 => $result['email'],
                    'contactNumber'         => $result['contact_number'],
                    'profileLink'           => $result['profile_link'],
                    'status'                => WorkerStatus::from($result['status']),
                    'jobTitles'             => new JobTitleContainer(explode(',', $result['job_titles'] ?? '')),
                    'additionalInfo'        => [
                        'totalTasks'        => (int)$result['total_tasks'],
                        'completedTasks'    => (int)$result['completed_tasks'],
                        'totalProjects'     => (int)$result['total_projects'],
                        'completedProjects' => (int)$result['completed_projects'],
                    ],
                    'createdAt'             => $result['created_at'],
                    'confirmedAt'           => $result['confirmed_at'],
                    'deletedAt'             => $result['deleted_at']
                ]);

                if ($includeHistory) {
                    $projects = new ProjectContainer();

                    $projectLists = json_decode($result['projectHistory'], true);
                    foreach ($projectLists as &$project) {
                        $entry = Project::createPartial([
                            'id'                        => $project['id'],
                            'publicId'                  => UUID::fromHex($project['public_id']),
                            'name'                      => $project['name'],
                            'status'                    => WorkStatus::from($project['status']),
                            'startDateTime'             => $project['start_date_time'],
                            'completionDateTime'        => $project['completion_date_time'],
                            'actualCompletionDateTime'  => $project['actual_completion_date_time'],
                            'additionalInfo'            => [
                                'workerStatus'          => WorkerStatus::from($project['worker_status']),
                            ]
                        ]);

                        $phaseLists = json_decode($project['project_phases'], true);
                        foreach ($phaseLists as $phase) {
                            $phaseEntry = Phase::createPartial([
                                'id'                        => $phase['id'],
                                'publicId'                  => UUID::fromHex($phase['public_id']),
                                'name'                      => $phase['name'],
                                'status'                    => WorkStatus::from($phase['status']),
                                'startDateTime'             => $phase['start_date_time'],
                                'completionDateTime'        => $phase['completion_date_time'],
                                'actualCompletionDateTime'  => $phase['actual_completion_date_time'],
                            ]);

                            $taskLists = json_decode($phase['tasks'], true);
                            foreach ($taskLists as $task) {
                                $phaseEntry->addTask(
                                    Task::createPartial([
                                        'id'                        => $task['id'],
                                        'publicId'                  => UUID::fromHex($task['public_id']),
                                        'name'                      => $task['name'],
                                        'status'                    => WorkStatus::from($task['status']),
                                        'priority'                  => TaskPriority::from($task['priority']),
                                        'startDateTime'             => $task['start_date_time'],
                                        'completionDateTime'        => $task['completion_date_time'],
                                        'actualCompletionDateTime'  => $task['actual_completion_date_time'],
                                        'additionalInfo'            => [
                                            'workerStatus'          => WorkerStatus::from($task['worker_status']),
                                        ]
                                    ])
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
     * the user, projectWorker, and project tables. It also LEFT JOINs userJobTitle to aggregate job titles for each worker.
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
    public static function findByProjectId(
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
                'orderBy'   => 'u.last_name ASC',
            ];  

            return self::find('', [], $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
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
     * @param array $data Array of worker public UUIDs or binary IDs to be assigned to the project.
     *
     * @throws InvalidArgumentException If the data array is empty.
     * @throws DatabaseException If a database error occurs during the transaction.
     * 
     * @return void
     */
    public static function createMultiple(int|UUID $projectId, array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('No data provided.');
        }

        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $projectId = ($projectId instanceof UUID)
            ? UUID::toBinary($projectId)
            : $projectId;

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $insertQuery = "
                INSERT INTO `project_worker` (
                    project_id, 
                    worker_id, 
                    status
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
                    :status
                ) ON DUPLICATE KEY UPDATE 
                    status = VALUES(status)";
            $statement = $instance->connection->prepare($insertQuery);
            foreach ($data as $id) {    
                $statement->execute([
                    ':projectId'    => $projectId,
                    ':workerId'     => ($id instanceof UUID)
                        ? UUID::toBinary($id)
                        : $id,
                    ':status'       => WorkerStatus::ASSIGNED->value
                ]);
            }

            $instance->connection->commit();
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
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
	public static function create(mixed $data): mixed
	{
        // Not implemented (No use case)
		return null;
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
    public static function worksOn(int|UUID $projectId, int|UUID $userId): bool
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        if (is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided.');
        }

        try {
            $instance = new self();
            $query = "
                SELECT *
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
            $statement = $instance->connection->prepare($query);
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
            return $instance->hasData($statement->fetchAll());
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Updates a project-worker relationship record in the database.
     *
     * This method updates fields of a project-worker association, identified either by its internal numeric ID,
     * or by a combination of project_id and worker_id (which may be integers or UUIDs). Only fields present in the
     * $data array will be updated. If no updatable fields are provided, the method is a no-op and returns true.
     *
     * Transaction is used to ensure atomicity. If an error occurs, the transaction is rolled back and a
     * DatabaseException is thrown.
     *
     * @param array $data Associative array containing update data with the following keys:
     *      - id: int (optional) Internal projectWorker record ID. If not provided, both project_id and worker_id are required.
     *      - projectId: int|UUID (optional) Project identifier (internal ID or UUID). Required if id is not provided.
     *      - workerId: int|UUID (optional) Worker identifier (internal ID or UUID). Required if id is not provided.
     *      - status: int|string|WorkerStatus (optional) New status for the project-worker relationship.
     *
     * @throws InvalidArgumentException If neither id nor both project_id and worker_id are provided.
     * @throws DatabaseException If a database error occurs during the update.
     *
     * @return bool True on successful update or if nothing to update.
     */
	public static function save(array $data): bool
	{
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $updateFields = [];
            $params = [];

            // Determine identifier clause: prefer numeric/internal id when provided
            if (isset($data['id'])) {
                if (!is_int($data['id']) || $data['id'] < 1) {
                    throw new InvalidArgumentException('Invalid Project Worker ID provided.');
                }

                $where = 'id = :id';
                $params[':id'] = $data['id'];
            } else {
                // Require project_id and worker_id when id is not provided
                if (!isset($data['projectId'])) {
                    throw new InvalidArgumentException('Project ID is required.');
                }

                if (!isset($data['workerId'])) {
                    throw new InvalidArgumentException('Worker ID is required.');
                }

                $whereParts = [];
                // project_id may be int or UUID
                if ($data['projectId'] instanceof UUID) {
                    $whereParts[] = 'project_id = (SELECT id FROM `project` WHERE public_id = :projectPublicId)';
                    $params[':projectPublicId'] = UUID::toBinary($data['projectId']);
                } else {
                    $whereParts[] = 'project_id = :projectId';
                    $params[':projectId'] = $data['projectId'];
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

            $query = 'UPDATE `project_worker` SET ' . implode(', ', $updateFields) . ' WHERE ' . $where;
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
    public static function delete(mixed $data): bool
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
            $query = "
                DELETE FROM
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
                            public_id = :workerId)') . "
            ";

            $instance = new self();
            $statement = $instance->connection->prepare($query);
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
