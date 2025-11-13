<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\JobTitleContainer;
use App\Container\PhaseContainer;
use App\Container\TaskContainer;
use App\Dependent\Phase;
use App\Enumeration\TaskPriority;
use App\Model\UserModel;
use App\Model\TaskModel;
use App\Core\UUID;
use App\Enumeration\WorkStatus;
use App\Container\ProjectContainer;
use App\Container\WorkerContainer;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Task;
use App\Dependent\Worker;
use App\Enumeration\Role;
use App\Core\Me;
use App\Core\Connection;
use App\Enumeration\WorkerStatus;
use App\Exception\ValidationException;
use App\Exception\DatabaseException;
use App\Validator\UuidValidator;
use InvalidArgumentException;
use DateTime;
use Exception;
use PDOException;

class ProjectModel extends Model
{
    /**
     * Find projects matching a SQL WHERE clause and return a ProjectContainer of results.
     *
     * Builds and executes a SELECT query on the `project` table using a provided WHERE clause
     * and bound parameters. The method optionally supports pagination and ordering via the
     * $options array. Each resulting row is converted to a Project object using Project::fromArray
     * and added to a ProjectContainer which is returned. If no rows are found, null is returned.
     *
     * Notes and behavior details:
     * - The base query is "SELECT * FROM `project` WHERE $whereClause". Therefore $whereClause
     *   must be a valid SQL condition (e.g. "status = :status AND created_by = :userId") and
     *   should not include the "WHERE" keyword itself.
     * - $params are passed to PDOStatement::execute and should match the placeholders used in
     *   $whereClause (named or positional).
     * - $options may contain:
     *      - 'limit'  => int Limits the number of returned rows (cast to int before use).
     *      - 'offset' => int Offsets the result set (cast to int before use).
     *      - 'orderBy'=> string An ORDER BY clause fragment (injected verbatim into SQL).
     * - limit and offset are explicitly cast to integers before being appended to the query.
     * - orderBy is appended directly into the SQL string — it must be trusted or sanitized
     *   by the caller to avoid SQL injection.
     *
     * Return value:
     * - Returns a ProjectContainer populated with Project instances when one or more rows are found.
     * - Returns null if the query yields no rows.
     *
     * @param string $whereClause SQL condition fragment (without the "WHERE" keyword). Required to be valid SQL.
     * @param array $params Positional or named parameters to bind to the prepared statement; values must correspond to placeholders in $whereClause.
     * @param array $options Optional settings:
     *      - limit: int (optional) Maximum number of rows to return.
     *      - offset: int (optional) Number of rows to skip.
     *      - orderBy: string (optional) ORDER BY clause fragment (e.g. "created_at DESC").
     *
     * @return ProjectContainer|null ProjectContainer with Project instances, or null if no rows found.
     *
     * @throws DatabaseException If a PDOException occurs during query preparation or execution (PDOException message is wrapped).
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?ProjectContainer
    {
        $instance = new self();
        try {
            $queryString = "
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
            ";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause), 
                $options);

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $projects = new ProjectContainer();
            foreach ($result as $row) {
                $row['manager'] = User::createPartial([
                    'id'            => $row['managerId'],
                    'publicId'      => UUID::fromBinary($row['managerPublicId']),
                    'firstName'     => $row['managerFirstName'],
                    'middleName'    => $row['managerMiddleName'],
                    'lastName'      => $row['managerLastName'],
                    'gender'        => $row['managerGender'],
                    'email'         => $row['managerEmail'],
                    'profileLink'   => $row['managerProfileLink'],
                ]);
                $projects->add(Project::createPartial($row));
            }
            return $projects;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * Retrieves a Project instance with optional related data.
     *
     * This method fetches a project by its UUID and can include associated phases, tasks, and workers
     * based on the provided options. The returned Project object is fully populated with its manager,
     * and optionally with its phases, tasks, and workers, each mapped to their respective domain objects.
     *
     * Query details:
     * - Always includes project manager as a JSON object.
     * - Optionally includes phases, tasks, and workers as JSON arrays if specified in $options.
     * - Uses dynamic SQL to build the query based on requested data.
     * - Converts database results into domain objects (Project, Phase, Task, Worker).
     *
     * @param UUID $projectId The UUID of the project to retrieve.
     * @param array $options Optional associative array to specify related data to include:
     *      - phases: bool Whether to include project phases (default: false)
     *      - tasks: bool Whether to include project tasks (default: false)
     *      - workers: bool Whether to include project workers (default: false)
     *
     * @return mixed Returns a fully populated Project instance if found, or null if not found.
     *
     * @throws DatabaseException If a database error occurs during retrieval.
     */
    public static function findFull(
        UUID $projectId, 
        array $options = [
            'phases' => false,
            'tasks' => false,
            'workers' => false
        ]
    ): mixed {
        $instance = new self();
        try {
            // Default options
            $includePhases = $options['phases'] ?? false;
            $includeTasks = $options['tasks'] ?? false;
            $includeWorkers = $options['workers'] ?? false;

            // Build dynamic query parts
            $selectFields = [
                'p.id AS projectId',
                'p.publicId AS projectPublicId',
                'p.name AS projectName',
                'p.description AS projectDescription',
                'p.budget AS projectBudget',
                'p.status AS projectStatus',
                'p.startDateTime AS projectStartDateTime',
                'p.completionDateTime AS projectCompletionDateTime',
                'p.actualCompletionDateTime AS projectActualCompletionDateTime',
                'p.createdAt AS projectCreatedAt',
                
                // Manager JSON object (always included)
                "JSON_OBJECT(
                    'managerId', m.id,
                    'managerPublicId', HEX(m.publicId),
                    'managerFirstName', m.firstName,
                    'managerMiddleName', m.middleName,
                    'managerLastName', m.lastName,
                    'managerEmail', m.email,
                    'managerProfileLink', m.profileLink,
                    'managerGender', m.gender,
                    'managerJobTitles', COALESCE(
                        (
                            SELECT CONCAT('[', GROUP_CONCAT(CONCAT('\"', mjt.title, '\"')), ']')
                            FROM userJobTitle AS mjt
                            WHERE mjt.userId = m.id
                        ),
                        '[]'
                    )
                ) AS projectManager"
            ];

            // Conditionally add phases subquery
            if ($includePhases) {
                $selectFields[] = "COALESCE(
                    (
                        SELECT CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT(
                                'phaseId', pp.id,
                                'phasePublicId', HEX(pp.publicId),
                                'phaseName', pp.name,
                                'phaseDescription', pp.description,
                                'phaseStatus', pp.status,
                                'phaseStartDateTime', pp.startDateTime,
                                'phaseCompletionDateTime', pp.completionDateTime
                            )
                            ORDER BY pp.startDateTime ASC
                        ), ']')
                        FROM projectPhase pp
                        WHERE pp.projectId = p.id
                    ),
                    '[]'
                ) AS projectPhases";
            }

            // Conditionally add workers subquery
            if ($includeWorkers) {
                $selectFields[] = "COALESCE(
                    (
                        SELECT CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT(
                                'workerId', w.id,
                                'workerPublicId', HEX(w.publicId),
                                'workerFirstName', w.firstName,
                                'workerMiddleName', w.middleName,
                                'workerLastName', w.lastName,
                                'workerEmail', w.email,
                                'workerProfileLink', w.profileLink,
                                'workerGender', w.gender,
                                'workerStatus', pw.status,
                                'workerJobTitles', COALESCE(
                                    (
                                        SELECT CONCAT('[', GROUP_CONCAT(CONCAT('\"', wjt.title, '\"')), ']')
                                        FROM userJobTitle wjt
                                        WHERE wjt.userId = w.id
                                    ),
                                    '[]'
                                )
                            ) ORDER BY w.lastName SEPARATOR ','
                        ), ']')
                        FROM projectWorker pw
                        INNER JOIN user w ON pw.workerId = w.id
                        WHERE pw.projectId = p.id
                    ),
                    '[]'
                ) AS projectWorkers";
            }

            // Select all tasks associated with the project
            if ($includeTasks) {
                $selectFields[] = "COALESCE(
                    (
                        SELECT CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT(
                                'taskId', pt.id,
                                'taskPublicId', HEX(pt.publicId),
                                'taskName', pt.name,
                                'taskDescription', pt.description,
                                'taskStatus', pt.status,
                                'taskPriority', pt.priority,
                                'taskStartDateTime', pt.startDateTime,
                                'taskCompletionDateTime', pt.completionDateTime,
                                'taskActualCompletionDateTime', pt.actualCompletionDateTime,
                                'taskCreatedAt', pt.createdAt
                            ) ORDER BY pt.createdAt SEPARATOR ','
                        ), ']')
                        FROM phaseTask AS pt
                        LEFT JOIN `projectPhase` AS pp ON pt.phaseId = pp.id
                        LEFT JOIN `project` AS p2 ON pp.projectId = p2.id
                        WHERE p2.id = p.id
                    ),
                    '[]'
                ) AS projectTasks";
            }

            // Build the final query
            $query = "
                SELECT *
                FROM (
                    SELECT 
                        " . implode(",\n                        ", $selectFields) . "
                    FROM 
                        project p
                    INNER JOIN
                        user m ON p.managerId = m.id
                    WHERE 
                        p.publicId = :projectId
                ) AS projectData
            ";

            $statement = $instance->connection->prepare($query);
            $statement->execute([':projectId' => UUID::toBinary($projectId)]);
            $result = $statement->fetch();

            // Process result into Project object
            if (!$instance->hasData($result)) {
                return null;
            }

            $mangerData = json_decode($result['projectManager'], true);
            $manager = User::createPartial([
                'id'            => $mangerData['managerId'],
                'publicId'      => UUID::fromHex($mangerData['managerPublicId']),
                'firstName'     => $mangerData['managerFirstName'],
                'middleName'    => $mangerData['managerMiddleName'],
                'lastName'      => $mangerData['managerLastName'],
                'email'         => $mangerData['managerEmail'],
                'profileLink'   => $mangerData['managerProfileLink'],
                'jobTitles'    => new JobTitleContainer(json_decode($mangerData['managerJobTitles'], true))
            ]);

            $project = new Project(
                id: $result['projectId'],
                publicId: UUID::fromBinary($result['projectPublicId']),
                name: $result['projectName'],
                description: $result['projectDescription'],
                manager: $manager,
                budget: $result['projectBudget'],
                tasks: new TaskContainer(),
                workers: new WorkerContainer(),
                phases: new PhaseContainer(),
                startDateTime: new DateTime($result['projectStartDateTime']),
                completionDateTime: new DateTime($result['projectCompletionDateTime']),
                actualCompletionDateTime: $result['projectActualCompletionDateTime'] 
                    ? new DateTime($result['projectActualCompletionDateTime']) 
                    : null,
                status: WorkStatus::from($result['projectStatus']),
                createdAt: new DateTime($result['projectCreatedAt']),
            );

            // Process phases if included
            if ($includePhases && isset($result['projectPhases'])) {
                $projectPhases = json_decode($result['projectPhases'], true);
                foreach ($projectPhases as $phase) {
                    $project->addPhase(new Phase(
                        id: $phase['phaseId'],
                        publicId: UUID::fromHex($phase['phasePublicId']),
                        name: $phase['phaseName'],
                        description: $phase['phaseDescription'],
                        startDateTime: new DateTime($phase['phaseStartDateTime']),
                        completionDateTime: new DateTime($phase['phaseCompletionDateTime']),
                        status: WorkStatus::from($phase['phaseStatus']),
                        tasks: new TaskContainer()
                    ));
                }
            }

            // Process tasks if included
            if ($includeTasks && isset($result['projectTasks'])) {
                $projectTask = json_decode($result['projectTasks'], true);
                foreach ($projectTask as $task) {
                    $project->addTask(new Task(
                        id: $task['taskId'],
                        publicId: UUID::fromHex($task['taskPublicId']),
                        name: $task['taskName'],
                        description: $task['taskDescription'],
                        status: WorkStatus::from($task['taskStatus']),
                        priority: TaskPriority::from($task['taskPriority']),
                        workers: new WorkerContainer(),
                        startDateTime: new DateTime($task['taskStartDateTime']),
                        completionDateTime: new DateTime($task['taskCompletionDateTime']),
                        actualCompletionDateTime: $task['taskActualCompletionDateTime'] 
                            ? new DateTime($task['taskActualCompletionDateTime']) 
                            : null,
                        createdAt: new DateTime($task['taskCreatedAt'])
                    ));
                }
            }

            // Process workers if included
            if ($includeWorkers && isset($result['projectWorkers'])) {
                $projectWorkers = json_decode($result['projectWorkers'], true);
                foreach ($projectWorkers as $worker) {
                    $project->addWorker(Worker::createPartial([
                        'id'            => $worker['workerId'],
                        'publicId'      => UUID::fromHex($worker['workerPublicId']),
                        'firstName'     => $worker['workerFirstName'],
                        'middleName'    => $worker['workerMiddleName'] ?? null,
                        'lastName'      => $worker['workerLastName'],
                        'email'         => $worker['workerEmail'] ?? null,
                        'profileLink'   => $worker['workerProfileLink'] ?? null,
                        'status'        => WorkerStatus::from($worker['workerStatus']),
                        'jobTitles'     => new JobTitleContainer(json_decode($worker['workerJobTitles'], true))
                    ]));
                }
            }
                    
            return $project;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves a Project by its ID.
     *
     * Validates the provided project ID, performs a parameterized lookup, and returns the first matching
     * Project instance or null when no record is found.
     *
     * Behavior:
     * - Throws InvalidArgumentException when $projectId is less than 1.
     * - Uses a parameterized query (named parameter :projectId) to fetch the project, mitigating SQL injection.
     * - Returns the first matching Project or null if none exists.
     * - Catches low-level PDOException and rethrows it as a DatabaseException while preserving the original message.
     *
     * @param int|UUID $projectId The numeric ID or UUID of the project to retrieve.
     *
     * @return Project|null The matching Project instance, or null if not found
     *
     * @throws InvalidArgumentException If the provided $projectId is invalid (< 1)
     * @throws DatabaseException If a database error occurs (wraps the underlying PDOException)
     */
    public static function findById(int|UUID $projectId): ?Project
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        try {
            $whereClause = is_int($projectId) 
                ? 'p.id = :projectId' 
                : 'p.publicId = :projectId';

            $params['projectId'] = is_int($projectId) 
                ? $projectId 
                : UUID::toBinary($projectId);

            $projects = self::find($whereClause, $params);
            return $projects->first() ?? null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds all projects managed by a specific manager.
     *
     * This method retrieves projects from the database where the managerId matches
     * the provided ID. It validates the manager ID before executing the query and
     * handles potential database errors.
     *
     * @param int|UUID $managerId The ID (integer or UUID) of the manager whose projects to retrieve
     * @param WorkStatus|null $status Optional status filter to retrieve projects with a specific status
     * 
     * @return ProjectContainer|null Container with projects managed by the specified manager,
     *                               or null if no projects are found.
     * 
     * @throws InvalidArgumentException If the provided manager ID is less than 1.
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    public static function findByManagerId(int|UUID $managerId, WorkStatus|null $status = null): ?ProjectContainer
    {
        if (is_int($managerId) && $managerId < 1) {
            throw new InvalidArgumentException('Invalid manager ID provided.');
        }

        try {
            $whereClause = is_int($managerId) 
                ? 'p.managerId = :managerId' 
                : 'p.managerId = (SELECT id FROM user WHERE publicId = :managerId)';

            $params['managerId'] = is_int($managerId) 
                ? $managerId
                : UUID::toBinary($managerId);

            if ($status) {
                $whereClause .= ' AND p.status = :status';
                $params['status'] = $status->value;
            }

            return self::find($whereClause, $params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds projects associated with a given worker (user) ID.
     *
     * This method validates the provided worker ID and queries the database for
     * projects that have an entry in the projectWorker table referencing the worker:
     * - Validates that $workerId is a positive integer (>= 1)
     * - Executes a SELECT with a subquery: id IN (SELECT projectId FROM projectWorker WHERE userId = :workerId)
     * - Optionally filters by project status if $status is provided
     * - Uses a prepared/bound parameter (:workerId) to avoid SQL injection
     * - Wraps lower-level PDO exceptions in a DatabaseException
     *
     * @param int $workerId Positive integer ID of the worker whose projects should be retrieved
     * @param WorkStatus|null $status Optional status filter to retrieve projects with a specific status
     *
     * @return ProjectContainer|null ProjectContainer containing the found project(s) or null if none found
     *
     * @throws InvalidArgumentException If $workerId is not a valid positive integer
     * @throws DatabaseException If a database error occurs (wraps the underlying PDOException)
     */
    public static function findByWorkerId(int|UUID $workerId, WorkStatus|null $status = null): ?ProjectContainer
    {
        if (is_int($workerId) && $workerId < 1) {
            throw new InvalidArgumentException('Invalid worker ID provided.');
        }

        try {
            $whereClause = is_int($workerId) 
                ? 'p.id IN (
                    SELECT projectId 
                    FROM projectWorker 
                    WHERE workerId = :workerId
                )' 
                : 'p.id IN (
                    SELECT projectId 
                    FROM projectWorker pw
                    INNER JOIN user u ON pw.workerId = u.id
                    WHERE u.publicId = :workerId
                )';

            $param['workerId'] = is_int($workerId) 
                ? $workerId
                : UUID::toBinary($workerId);

            if ($status) {
                $whereClause .= ' AND p.status = :status';
                $param['status'] = $status->value;
            }

            return self::find($whereClause,$param);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds all active projects managed by a specific manager.
     *
     * This method retrieves all projects where the manager ID matches the provided ID
     * and the project status is not COMPLETED. Active projects include all statuses
     * except for completed ones (e.g., pending, in progress, on hold, etc.).
     *
     * @param int $managerId The ID of the manager whose active projects to retrieve
     * 
     * @return Project|null Active Project, or null if none found
     * 
     * @throws InvalidArgumentException If managerId is less than 1
     * @throws DatabaseException If a database error occurs during the query
     */
    public static function findManagerActiveProjectByManagerId(int $managerId): ?Project
    {
        if ($managerId < 1) {
            throw new InvalidArgumentException('Invalid manager ID provided.');
        }

        try {
            $whereClause = 'p.managerId = :managerId AND p.status != :completedStatus AND p.status != :cancelledStatus';
            $param = [
                ':managerId'        => $managerId,
                ':completedStatus'  => WorkStatus::COMPLETED->value,
                ':cancelledStatus'  => WorkStatus::CANCELLED->value,
            ];
            $options = [
                'limit'     => 1,
                'orderBy'   => 'createdAt DESC',
            ];

            $projects = self::find($whereClause, $param, $options);
            return $projects?->first() ?? null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds all active projects assigned to a specific worker.
     *
     * This method retrieves all projects where the worker is assigned and the project
     * status is not completed. It performs a subquery to find all project IDs from the
     * projectWorker table that are associated with the given worker ID, then filters
     * out any projects with a COMPLETED status.
     *
     * @param int $workerId The ID of the worker whose active projects should be retrieved
     * 
     * @return Project|null Active Project, or null if none found
     * 
     * @throws InvalidArgumentException If the worker ID is less than 1
     * @throws DatabaseException If a database error occurs during the query execution
     */
    public static function findWorkerActiveProjectByWorkerId(int $workerId): ?Project
    {
        if ($workerId < 1) {
            throw new InvalidArgumentException('Invalid worker ID provided.');
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
                    `projectWorker` AS pw
                ON	
                    pw.projectId = p.id
                WHERE	
                    pw.workerId = :workerId
                AND
                    p.status != :completedStatus
                AND 
                    p.status != :cancelledStatus
                AND
                    pw.status != :terminatedStatus
                LIMIT 1
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':workerId'         => $workerId,
                ':completedStatus'  => WorkStatus::COMPLETED->value,
                ':cancelledStatus'  => WorkStatus::CANCELLED->value,
                ':terminatedStatus' => WorkerStatus::TERMINATED->value,
            ]);
            $result = $statement->fetch();

            if (!$instance->hasData($result)) {
                return null;
            }

            $result['manager'] = User::createPartial([
                'id'            => $result['managerId'],
                'publicId'      => UUID::fromBinary($result['managerPublicId']),
                'firstName'     => $result['managerFirstName'],
                'middleName'    => $result['managerMiddleName'],
                'lastName'      => $result['managerLastName'],
                'gender'        => $result['managerGender'],
                'email'         => $result['managerEmail'],
                'profileLink'   => $result['managerProfileLink'],
            ]);
            return Project::createPartial($result);;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Searches for projects based on provided criteria.
     *
     * This static method allows searching for projects using a keyword, user ID (either integer or UUID),
     * project status, and additional options such as pagination and sorting. It constructs a dynamic SQL
     * WHERE clause based on the provided parameters and delegates the actual data retrieval to the `find` method.
     *
     * - If a search key is provided, it performs a full-text search on project name and description.
     * - If a user ID is provided, it filters projects managed by or assigned to the user (supports both integer and UUID).
     * - If a status is provided, it filters projects by the specified status.
     * - Additional options can be set for offset, limit, and order.
     *
     * @param string $key Optional search keyword for full-text search on project name and description.
     * @param int|UUID|null $userId Optional user identifier (integer ID or UUID) to filter projects by manager or worker.
     * @param WorkStatus|null $status Optional project status to filter results.
     * @param array $options Optional associative array for query options:
     *      - offset: int (default 0) Number of records to skip.
     *      - limit: int (default 10) Maximum number of records to return.
     *      - orderBy: string (default 'createdAt DESC') SQL ORDER BY clause.
     *
     * @throws InvalidArgumentException If an invalid user ID is provided.
     * @throws DatabaseException If a database error occurs during the search.
     *
     * @return ProjectContainer|null A container of found projects, or null if no projects match the criteria.
     */
    public static function search(
        string $key = '',
        int|UUID|null $userId = null,
        WorkStatus|null $status = null,
        array $options = [
            'offset'    => 0,
            'limit'     => 10,
            'orderBy'   => 'createdAt DESC',
        ]
    ): ?ProjectContainer {
        if (isset($userId) && is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided.');
        }

        try {
            $whereClauses = [];
            $params = [];

            if (trimOrNull($key)) {
                $whereClauses[] = 'MATCH(p.name, p.description) AGAINST (:searchKey IN NATURAL LANGUAGE MODE)';
                $params[':searchKey'] = $key;
            }

            if ($userId) {
                if (is_int($userId)) {
                    $whereClauses[] = '(p.managerId = :userId1 
                    OR p.id IN (
                        SELECT projectId 
                        FROM projectWorker 
                        WHERE workerId = :userId2
                    ))';
                    $params[':userId1'] = $userId;
                    $params[':userId2'] = $userId;
                } else {
                    $whereClauses[] = '(p.managerId = (SELECT id FROM user WHERE publicId = :userId1) 
                    OR p.id IN (
                        SELECT projectId 
                        FROM projectWorker 
                        WHERE workerId = (SELECT id FROM user WHERE publicId = :userId2)
                    ))';
                    $params[':userId1'] = UUID::toBinary($userId);
                    $params[':userId2'] = UUID::toBinary( $userId);
                }
            }

            if ($status) {
                $whereClauses[] = 'p.status = :status';
                $params[':status'] = $status->value;
            }

            $whereClause = implode(' AND ', $whereClauses);

            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves a paginated collection of Project entities.
     *
     * This method validates pagination parameters and delegates the actual data retrieval
     * to the model's find mechanism. Behavior details:
     * - Validates that $offset is >= 0; otherwise throws InvalidArgumentException.
     * - Validates that $limit is >= 1; otherwise throws InvalidArgumentException.
     * - Calls self::find with an empty filter and options including:
     *      - offset => $offset
     *      - limit  => $limit
     *      - orderBy => 'createdAt DESC' (newest projects first)
     * - Catches low-level PDOException and rethrows it as a DatabaseException preserving the message.
     *
     * @param int $offset Zero-based offset of the first record to return. Must be >= 0. Default: 0.
     * @param int $limit  Maximum number of records to return. Must be >= 1. Default: 10.
     *
     * @return ProjectContainer|null Container of Project entities for the requested page, or null if none found.
     *
     * @throws InvalidArgumentException If $offset is negative or $limit is less than 1.
     * @throws DatabaseException If a database error occurs while fetching projects (wraps PDOException).
     */
    public static function all(int $offset = 0, int $limit = 10): ?ProjectContainer
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
                'orderBy'   => 'createdAt DESC',
            ];
            return self::find('', [], $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates and persists a new Project instance to the database.
     *
     * This method handles the complete creation of a project including:
     * - Validation of the Project instance
     * - Generation of UUID if not provided
     * - Insertion of project data into the database
     * - Creation of associated project phases if any exist
     * - Transaction management to ensure data integrity
     *
     * @param mixed $project Project instance to be created. Must be an instance of Project class.
     *
     * @return Project The created Project instance with updated id and publicId from database
     *
     * @throws InvalidArgumentException If the provided parameter is not an instance of Project
     * @throws DatabaseException If any database operation fails during the transaction
     *
     */
    public static function create(mixed $project): Project
    {
        if (!($project instanceof Project)) {
            throw new InvalidArgumentException('Expected instance of Project.');
        }

        $instance = new self();

        try {
            $instance->connection->beginTransaction();

            $projectPublicId           =   $project->getPublicId() ?? UUID::get();
            $projectName               =   trimOrNull($project->getName());
            $projectDescription        =   trimOrNull($project->getDescription());
            $projectBudget             =   ($project->getBudget()) ?? 0.00;
            $projectStatus             =   $project->getStatus() ?? WorkStatus::PENDING;
            $projectStartDateTime      =   formatDateTime($project->getStartDateTime());
            $projectCompletionDateTime =   formatDateTime($project->getCompletionDateTime());
            $projectPhases             =   $project->getPhases();

            
            $projectQuery = "
                INSERT INTO `project` (
                    publicId,
                    name,
                    description,
                    budget,
                    status,
                    startDateTime,
                    completionDateTime,
                    managerId
                ) VALUES (
                    :publicId,
                    :name,
                    :description,
                    :budget,
                    :status,
                    :startDateTime,
                    :completionDateTime,
                    :managerId
                )";
            $statement = $instance->connection->prepare($projectQuery);
            $statement->execute([
                ':publicId'             => UUID::toBinary($projectPublicId),
                ':name'                 => $projectName,
                ':description'          => $projectDescription,
                ':budget'               => $projectBudget,
                ':status'               => $projectStatus->value,
                ':startDateTime'        => $projectStartDateTime,
                ':completionDateTime'   => $projectCompletionDateTime,
                ':managerId'            => Me::getInstance()->getId(),
            ]);
            $projectId = intval($instance->connection->lastInsertId());

            if ($projectPhases && $projectPhases->count() > 0) {
                $projectPhaseQuery = "
                    INSERT INTO `projectPhase` (
                        projectId,
                        publicId,
                        name,
                        description,
                        startDateTime,
                        completionDateTime,
                        status
                    ) VALUES (
                        :projectId,
                        :publicId,
                        :name,
                        :description,
                        :startDateTime,
                        :completionDateTime,
                        :status
                    )";
                $phaseStatement = $instance->connection->prepare($projectPhaseQuery);                       
                foreach ($projectPhases as $phase) {
                    $phaseStatement->execute([
                        ':projectId'            => $projectId,
                        ':publicId'             => UUID::toBinary($phase->getPublicId()),
                        ':name'                 => $phase->getName(),
                        ':description'          => $phase->getDescription(),
                        ':startDateTime'        => formatDateTime($phase->getStartDateTime()),
                        ':completionDateTime'   => formatDateTime($phase->getCompletionDateTime()),
                        ':status'               => $phase->getStatus()->value,
                    ]);
                }
            }

            $instance->connection->commit();

            $project->setId($projectId);
            $project->setPublicId($projectPublicId);
            return $project;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Saves or updates a project with its associated data in the database.
     *
     * This method performs a transactional update of project data. It dynamically builds
     * an UPDATE query based on provided fields and handles various data conversions:
     * - Trims string fields (name, description) or sets them to null
     * - Converts status enum to its value
     * - Formats DateTime objects to ATOM format for storage
     * - Handles nullable actualCompletionDateTime field
     * - Recursively saves associated tasks if provided
     * - Uses either 'id' or 'publicId' for identifying the project record
     *
     * @param array $data Associative array containing project data with following keys:
     *      - id: int|UUID Project ID to identify the project to update
     *      - name: string (optional) Project name
     *      - description: string (optional) Project description
     *      - budget: float|int (optional) Project budget amount
     *      - status: ProjectStatus (optional) Project status enum
     *      - startDateTime: DateTime|string (optional) Project start date and time
     *      - completionDateTime: DateTime|string (optional) Planned completion date and time
     *      - actualCompletionDateTime: DateTime|string|null (optional) Actual completion date and time
     *      - tasks: TaskContainer (optional) Container of associated Task objects to be saved
     * 
     * @return bool Returns true if save operation was successful
     * 
     * @throws InvalidArgumentException If required fields are missing or invalid
     * @throws DatabaseException If a database error occurs during the transaction
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
                    throw new InvalidArgumentException('Invalid Project ID provided.');
                }
                $params[':id'] = $data['id'];
            } elseif (isset($data['publicId']) && $data['publicId'] instanceof UUID) {
                $params[':id'] = UUID::toBinary($data['publicId']);
            } else {
                throw new InvalidArgumentException('Project ID or Public ID is required.');
            }

            if (isset($data['name'])) {
                $updateFields[] = 'name = :name';
                $params[':name'] = trimOrNull($data['name']);
            }

            if (isset($data['description'])) {
                $updateFields[] = 'description = :description';
                $params[':description'] = trimOrNull($data['description']);
            }

            if (isset($data['budget'])) {
                $updateFields[] = 'budget = :budget';
                $params[':budget'] = $data['budget'];
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
                $projectQuery = "
                    UPDATE `project` 
                    SET " . implode(', ', $updateFields) . 
                    " WHERE " . (
                        is_int($data['id']) 
                        ? 'id' 
                        : 'publicId') . " = :id";
                $statement = $instance->connection->prepare($projectQuery);
                $statement->execute($params);
            }

            if (isset($data['tasks']) && $data['tasks'] instanceof TaskContainer) {
                foreach ($data['tasks'] as $task) {
                    $task->save();
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