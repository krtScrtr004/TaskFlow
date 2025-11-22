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
use App\Dependent\ProjectReport;
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
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => $options[':groupBy'] ?? $options['groupBy'] ?? 'p.id',
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'p.startDateTime DESC',
        ];

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
                $paramOptions);

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
                            SELECT 
                                CONCAT('[', GROUP_CONCAT(CONCAT('\"', mjt.title, '\"')), ']')
                            FROM 
                                `userJobTitle` AS mjt
                            WHERE 
                                mjt.userId = m.id
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
                                'phaseCompletionDateTime', pp.completionDateTime,
                                'phaseActualCompletionDateTime', pp.actualCompletionDateTime
                            ) ORDER BY pp.startDateTime ASC SEPARATOR ','
                        ), ']')
                        FROM 
                            `projectPhase` pp
                        WHERE 
                            pp.projectId = p.id
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
                                'workerCreatedAt', w.createdAt,
                                'workerConfirmedAt', w.confirmedAt,
                                'workerDeletedAt', w.deletedAt,
                                'workerJobTitles', COALESCE(
                                    (
                                        SELECT 
                                            CONCAT('[', GROUP_CONCAT(CONCAT('\"', wjt.title, '\"')), ']')
                                        FROM 
                                            `userJobTitle` AS wjt
                                        WHERE 
                                            wjt.userId = w.id
                                    ),
                                    '[]'
                                )
                            ) ORDER BY w.lastName SEPARATOR ','
                        ), ']')
                        FROM 
                            `projectWorker` AS pw
                        INNER JOIN 
                            `user` AS w 
                        ON 
                            pw.workerId = w.id
                        WHERE 
                            pw.projectId = p.id
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
                            ) ORDER BY pt.startDateTime DESC SEPARATOR ','
                        ), ']')
                        FROM 
                            `phaseTask` AS pt
                        LEFT JOIN 
                            `projectPhase` AS pp 
                        ON 
                            pt.phaseId = pp.id
                        LEFT JOIN 
                            `project` AS p2 
                        ON 
                            pp.projectId = p2.id
                        WHERE 
                            p2.id = p.id
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
                        `project` AS p
                    INNER JOIN
                        `user` AS m 
                    ON 
                        p.managerId = m.id
                    WHERE 
                        p.publicId = :projectId
                    ORDER BY
                        p.startDateTime ASC
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
                        actualCompletionDateTime: $phase['phaseActualCompletionDateTime'] 
                            ? new DateTime($phase['phaseActualCompletionDateTime']) 
                            : null,
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
                        'jobTitles'     => new JobTitleContainer(json_decode($worker['workerJobTitles'], true)),
                        'createdAt'     => new DateTime($worker['workerCreatedAt']),
                        'confirmedAt'   => $worker['workerConfirmedAt'] ? new DateTime($worker['workerConfirmedAt']) : null,
                        'deletedAt'     => $worker['workerDeletedAt'] ? new DateTime($worker['workerDeletedAt']) : null,
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
                    SELECT 
                        projectId 
                    FROM 
                        `projectWorker`
                    WHERE   
                        workerId = :workerId
                )' 
                : 'p.id IN (
                    SELECT 
                        projectId 
                    FROM 
                        `projectWorker` AS pw
                    INNER JOIN 
                        user AS u 
                    ON 
                        pw.workerId = u.id
                    WHERE 
                        u.publicId = :workerId
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
                    u.profileLink AS managerProfileLink,
                    u.createdAt AS managerCreatedAt,
                    u.confirmedAt AS managerConfirmedAt,
                    u.deletedAt AS managerDeletedAt
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
                'createdAt'     => $result['managerCreatedAt'],
                'confirmedAt'   => $result['managerConfirmedAt'],
                'deletedAt'     => $result['managerDeletedAt'],
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
            'orderBy'   => 'p.startDateTime DESC',
        ]
    ): ?ProjectContainer {
        if (isset($userId) && is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided.');
        }

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'p.startDateTime DESC',
        ];

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
                        SELECT 
                            projectId 
                        FROM 
                            `projectWorker` 
                        WHERE 
                            workerId = :userId2
                    ))';
                    $params[':userId1'] = $userId;
                    $params[':userId2'] = $userId;
                } else {
                    $whereClauses[] = '(p.managerId = (SELECT id FROM user WHERE publicId = :userId1) 
                    OR p.id IN (
                        SELECT 
                            projectId 
                        FROM 
                            `projectWorker` 
                        WHERE 
                            workerId = (SELECT id FROM `user` WHERE publicId = :userId2)
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

            return self::find($whereClause, $params, $paramOptions);
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

    /**
     * Generates a ProjectReport for the given project identifier.
     *
     * This method retrieves and aggregates multiple data sources to assemble a complete
     * report object describing a project's overall status, phases, tasks, worker statistics,
     * periodic task counts and top-performing workers.
     *
     * Main behaviors and conversions:
     * - Validates integer project IDs (must be >= 1).
     * - Fetches:
     *     - project statistics (associative row containing project metadata and a JSON-encoded phases collection)
     *     - worker counts by status (associative counts including a 'total' key)
     *     - periodic task counts (rows with year, month, taskCount)
     *     - top workers (rows with worker metadata and aggregated metrics)
     * - Decodes JSON-encoded phase and task payloads via json_decode and iterates them to build containers:
     *     - PhaseContainer populated with Phase objects created via Phase::createPartial()
     *     - TaskContainer populated with Task objects created via Task::createPartial()
     * - Converts identifiers and typed values:
     *     - Task and Phase public IDs are converted from hex strings using UUID::fromHex()
     *     - Project public ID is converted from binary using UUID::fromBinary()
     *     - Priority and status values are converted to enums via TaskPriority::from() and WorkStatus::from()
     *     - Datetime strings are converted to DateTime instances; nullable actual completion timestamps are handled
     * - Computes worker status breakdown:
     *     - Builds per-status count and percentage (percentage computed as (count / total) * 100, or 0 when total is 0)
     * - Aggregates periodic task counts into a nested array keyed by year and month
     * - Builds a WorkerContainer of top workers; each Worker is created via Worker::createPartial() and given an
     *   additionalInfo array with totalTasks, completedTasks and overallScore
     * - Returns a ProjectReport constructed with the assembled data (id, publicId, name, datetimes, status,
     *   workerCount breakdown, periodicTaskCount aggregation, phases container and topWorker container)
     *
     * Expected structure of fetched rows / JSON payloads:
     * - projectStatistics (associative array):
     *     - projectId: int
     *     - projectPublicId: binary (converted via UUID::fromBinary)
     *     - projectName: string
     *     - projectStartDateTime: string (ISO datetime)
     *     - projectCompletionDateTime: string (ISO datetime)
     *     - projectActualCompletionDateTime: string|null
     *     - projectStatus: string (value for WorkStatus::from)
     *     - projectPhases: string (JSON array of phases)
     * - projectPhases JSON array element (phase):
     *     - phaseId: int
     *     - phasePublicId: string (hex; converted via UUID::fromHex)
     *     - phaseName: string
     *     - phaseStartDateTime: string
     *     - phaseCompletionDateTime: string
     *     - phaseActualCompletionDateTime: string|null
     *     - phaseStatus: string (value for WorkStatus::from)
     *     - phaseTasks: string (JSON array of tasks)
     * - phaseTasks JSON array element (task):
     *     - taskId: int
     *     - taskPublicId: string (hex; converted via UUID::fromHex)
     *     - taskName: string
     *     - taskPriority: string (value for TaskPriority::from)
     *     - taskStatus: string (value for WorkStatus::from)
     *     - taskStartDateTime: string
     *     - taskCompletionDateTime: string
     *     - taskActualCompletionDateTime: string|null
     * - workerCount (associative array):
     *     - total: int
     *     - <statusKey>: int (one or more status keys corresponding to WorkerStatus enum values)
     * - periodicTaskCount (array of rows):
     *     - year: int
     *     - month: int
     *     - taskCount: int
     * - topWorkers (array of rows):
     *     - id: int
     *     - firstName: string
     *     - lastName: string
     *     - email: string
     *     - totalTasks: int
     *     - completedTasks: int
     *     - overallScore: float|int
     *
     * Special cases:
     * - If both project statistics and top workers are absent (no data), the method returns null.
     *
     * @param int|UUID $projectId Project identifier (integer ID or UUID instance). Integer IDs must be >= 1.
     *
     * @return ProjectReport|null The assembled ProjectReport instance, or null if no report data is available.
     *
     * @throws InvalidArgumentException When an integer projectId less than 1 is provided.
     * @throws DatabaseException        When a PDOException occurs while querying the database (wrapped).
     */
    public static function getReport(int|UUID $projectId): ?ProjectReport
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $instance = new self();
        try {
            $projectStatistics = self::projectStatistics($projectId);
            $workerCount = self::workerCount($projectId);
            $periodicTaskCount = self::periodicTaskCount($projectId);
            $topWorkers = self::topWorkersQuery($projectId);
            
            if (!$projectStatistics && !$topWorkers) {
                return null;
            }

            // Build phases and tasks
            $phases = new PhaseContainer();
            $rawPhases = json_decode($projectStatistics['projectPhases'], true);
            foreach ($rawPhases as $phase) {
                $tasks = new TaskContainer();

                $rawTasks = json_decode($phase['phaseTasks'], true);
                foreach ($rawTasks as $task) {
                    $tasks->add(Task::createPartial([
                        'id'                        => $task['taskId'],
                        'publicId'                  => UUID::fromHex($task['taskPublicId']),
                        'name'                      => $task['taskName'],
                        'priority'                  => TaskPriority::from($task['taskPriority']),
                        'status'                    => WorkStatus::from($task['taskStatus']),
                        'startDateTime'             => new DateTime($task['taskStartDateTime']),
                        'completionDateTime'        => new DateTime($task['taskCompletionDateTime']),
                        'actualCompletionDateTime'  => $task['taskActualCompletionDateTime'] 
                            ? new DateTime($task['taskActualCompletionDateTime']) 
                            : null,
                    ]));
                }

                $phases->add(Phase::createPartial([
                    'id'                        => $phase['phaseId'],
                    'publicId'                  => UUID::fromHex($phase['phasePublicId']),
                    'name'                      => $phase['phaseName'],
                    'startDateTime'             => new DateTime($phase['phaseStartDateTime']),
                    'completionDateTime'        => new DateTime($phase['phaseCompletionDateTime']),
                    'actualCompletionDateTime'  => $phase['phaseActualCompletionDateTime'] 
                        ? new DateTime($phase['phaseActualCompletionDateTime']) 
                        : null,
                    'status'                    => WorkStatus::from($phase['phaseStatus']),
                    'tasks'                     => $tasks
                ]));
            }

            // Build Worker Count
            $workerCounts = [];
            $total = $workerCount['total'];
            foreach ($workerCount as $status => $count) {
                if ($status === 'total') {
                    continue;
                }

                $key = WorkerStatus::from($status);
                $workerCounts[$key->value]['count'] = $count;
                $workerCounts[$key->value]['percentage'] = $total > 0 ? ($count / $total) * 100 : 0;
            }

            $taskCounts = [];
            foreach($periodicTaskCount as $row) {
                $year = (int) $row['year'];
                $month = (int) $row['month'];
                $count = (int) $row['taskCount'];

                $taskCounts[$year][$month] = ($taskCounts[$year][$month] ?? 0) + $count;
            }

            // Build top workers
            $workers = new WorkerContainer();
            foreach ($topWorkers as $worker) {
                $workers->add(Worker::createPartial([
                    'id'                => $worker['id'],
                    'firstName'         => $worker['firstName'],
                    'lastName'          => $worker['lastName'],
                    'email'             => $worker['email'],
                    'additionalInfo'    => [
                        'totalTasks'        => $worker['totalTasks'],
                        'completedTasks'    => $worker['completedTasks'],
                        'overallScore'      => $worker['overallScore'],
                    ],
                ]));
            }

            $report = new ProjectReport(
                id: $projectStatistics['projectId'],
                publicId: UUID::fromBinary($projectStatistics['projectPublicId']),
                name: $projectStatistics['projectName'],
                startDateTime: new DateTime($projectStatistics['projectStartDateTime']),
                completionDateTime: new DateTime($projectStatistics['projectCompletionDateTime']),
                actualCompletionDateTime: $projectStatistics['projectActualCompletionDateTime'] 
                    ? new DateTime($projectStatistics['projectActualCompletionDateTime']) 
                    : null,
                status: WorkStatus::from($projectStatistics['projectStatus']),
                workerCount: $workerCounts,
                periodicTaskCount: $taskCounts,
                phases: $phases,
                topWorker: $workers
            );

            return $report;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves aggregated statistics for a project, including its phases and tasks.
     *
     * This private static method executes a single SQL query that returns project-level
     * fields and a nested JSON representation of phases and their tasks. The query:
     * - Matches the project by numeric ID (p.id) when an int is provided, or by its
     *   binary publicId when a UUID-like value is provided (the UUID is converted to
     *   binary via UUID::toBinary for binding).
     * - Aggregates phases into a JSON array (ordered by phase.startDateTime ASC).
     * - For each phase, aggregates its tasks into a JSON array (ordered by task.startDateTime ASC).
     *
     * Note on returned formats:
     * - Top-level project fields (projectId, projectName, projectStartDateTime, etc.) are
     *   returned as selected from the database.
     * - projectPublicId is returned as stored (binary UUID in the database) unless converted by the caller.
     * - Inside the aggregated JSON:
     *     - phasePublicId and taskPublicId are returned as hexadecimal strings (HEX(...)).
     *     - All date/time fields are returned as stored by the database (typically string timestamps).
     * - projectPhases is returned as a JSON string representing an array of phases. Each phase object contains:
     *     - phaseId: int
     *     - phasePublicId: string (hex representation)
     *     - phaseName: string
     *     - phaseStartDateTime: string|null
     *     - phaseCompletionDateTime: string|null
     *     - phaseActualCompletionDateTime: string|null
     *     - phaseStatus: string
     *     - phaseTasks: JSON string representing an array of tasks, where each task object contains:
     *         - taskId: int
     *         - taskPublicId: string (hex representation)
     *         - taskName: string
     *         - taskPriority: mixed (as stored)
     *         - taskStatus: string
     *         - taskStartDateTime: string|null
     *         - taskCompletionDateTime: string|null
     *         - taskActualCompletionDateTime: string|null
     *
     * Usage notes:
     * - The method prepares and executes a PDO statement and returns the first fetched row.
     * - If no project is found, the method returns null.
     *
     * @param int|UUID $projectId Project identifier; pass an int for internal ID or a UUID-like value for publicId.
     *
     * @return array|null Associative array of project fields with the following keys:
     *      - projectId: int
     *      - projectPublicId: string|binary (as stored in DB)
     *      - projectName: string
     *      - projectStartDateTime: string|null
     *      - projectCompletionDateTime: string|null
     *      - projectActualCompletionDateTime: string|null
     *      - projectStatus: string
     *      - projectPhases: string JSON array of phases (see structure described above)
     *
     */ 
    private static function projectStatistics(int|UUID $projectId)
    {
        $query = "
            SELECT 
                p.id AS projectId,
                p.publicId AS projectPublicId,
                p.name AS projectName,
                p.startDateTime AS projectStartDateTime,
                p.completionDateTime AS projectCompletionDateTime,
                p.actualCompletionDateTime AS projectActualCompletionDateTime,
                p.status AS projectStatus,
                (
                    SELECT CONCAT(
                        '[', 
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'phaseId', pp.id,
                                'phasePublicId', HEX(pp.publicId),
                                'phaseName', pp.name,
                                'phaseStartDateTime', pp.startDateTime,
                                'phaseCompletionDateTime', pp.completionDateTime,
                                'phaseActualCompletionDateTime', pp.actualCompletionDateTime,
                                'phaseStatus', pp.status,
                                'phaseTasks', (
                                    SELECT CONCAT(
                                        '[', 
                                        GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'taskId', pt.id,
                                                'taskPublicId', HEX(pt.publicId),
                                                'taskName', pt.name,
                                                'taskPriority', pt.priority,
                                                'taskStatus', pt.status,
                                                'taskStartDateTime', pt.startDateTime,
                                                'taskCompletionDateTime', pt.completionDateTime,
                                                'taskActualCompletionDateTime', pt.actualCompletionDateTime
                                            )
                                            ORDER BY pt.startDateTime ASC
                                            SEPARATOR ','
                                        ),
                                        ']'
                                    )
                                    FROM 
                                        `phaseTask` AS pt
                                    WHERE 
                                        pt.phaseId = pp.id
                                )
                            )
                            ORDER BY pp.startDateTime ASC SEPARATOR ','
                        ),
                        ']'
                    )
                    FROM 
                        `projectPhase` AS pp
                    WHERE 
                        pp.projectId = p.id
                ) AS projectPhases
            FROM
                `project` AS p
            WHERE
                " . (is_int($projectId) ? 'p.id = :projectId' : 'p.publicId = :projectId') . "
            LIMIT 1";

        $instance = new self();
        $statement = $instance->connection->prepare($query);
        $statement->execute([
            ':projectId'    => is_int($projectId) 
                ? $projectId 
                : UUID::toBinary($projectId),
        ]);
        $result = $statement->fetch();

        if (!$instance->hasData($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Returns worker counts for a given project.
     *
     * Executes a single SQL query that produces three counts for the specified project:
     *  - assigned:   Number of distinct phaseTaskWorker entries where the worker is assigned
     *                to a task whose phase belongs to the project, the task status is not
     *                'completed' or 'cancelled', and the projectWorker relation has status 'assigned'.
     *  - terminated: Number of projectWorker records for the project with status 'terminated'.
     *  - unassigned: Number of users with role 'worker' (confirmed and not deleted) who are
     *                not currently assigned to any active task (status 'assigned' on phaseTaskWorker
     *                and task not in ('completed','cancelled')) and who do not have a terminated
     *                projectWorker record for this project.
     *
     * The method accepts either an integer project id or a UUID public id. When a UUID is provided,
     * it is converted to the binary representation before being bound to the query.
     *
     * @param int|UUID $projectId Project identifier. Provide the numeric primary key (int) to query by p.id,
     *                            or a UUID (string|UUID object) to query by p.publicId.
     *
     * @return array|null Associative array with keys:
     *      - assigned: int Number of currently assigned workers on active tasks
     *      - terminated: int Number of terminated project workers
     *      - unassigned: int Number of available (not assigned, not terminated) workers
     *      - total: int Total number of workers associated with the project
     *      Returns null if the project was not found or no row was returned.
     */
    private static function workerCount(int|UUID $projectId) {
        $query = "
            SELECT 
                (
                    SELECT 
                        COUNT(DISTINCT ptw.workerId)
                    FROM
                        `phaseTaskWorker` AS ptw
                    INNER JOIN 
                        `phaseTask` AS pt 
                    ON 
                        pt.id = ptw.taskId
                    INNER JOIN 
                        `projectPhase` AS pp 
                    ON 
                        pp.id = pt.phaseId
                    INNER JOIN 
                        `projectWorker` AS pw 
                    ON 
                        pw.workerId = ptw.workerId
                    AND 
                        pw.projectId = pp.projectId
                    WHERE
                        pp.projectId = p.id
                    AND
                        ptw.status = 'assigned'
                    AND
                        pt.status NOT IN ('completed', 'cancelled')
                    AND
                        pw.status = 'assigned'
                ) AS assigned,
                (
                    SELECT
                        COUNT(DISTINCT pw.workerId)
                    FROM
                        `projectWorker` AS pw
                    INNER JOIN
                        `project` AS p2
                    ON
                        p2.id = pw.projectId
                    WHERE
                        pw.status = 'terminated'
                    AND 
                        p2.id = p.id
                ) AS 'terminated',
                (
                    SELECT
                        COUNT(DISTINCT u.id)
                    FROM
                        `user` AS u
                    WHERE
                        u.role = 'worker' 
                    AND 
                        u.confirmedAt IS NOT NULL
                    AND 
                        u.deletedAt IS NULL 
                    AND 
                        NOT EXISTS(
                            SELECT
                                1
                            FROM
                                `phaseTaskWorker` AS ptw
                            INNER JOIN 
                                `phaseTask` AS pt
                            ON
                                ptw.taskId = pt.id
                            WHERE
                                ptw.workerId = u.id 
                            AND
                                ptw.status = 'assigned' 
                            AND 
                                pt.status NOT IN('completed', 'cancelled')
                    ) AND NOT EXISTS(
                        SELECT
                            1
                        FROM
                            `projectWorker` AS pw3
                        WHERE
                            pw3.workerId = u.id 
                        AND 
                            pw3.projectId = p.id 
                        AND 
                            pw3.status = 'terminated'
                    )
                ) AS unassigned,
                (
                    SELECT 
                        COUNT(DISTINCT pw.workerId)
                    FROM
                        `projectWorker` AS pw
                    WHERE
                        pw.projectId = p.id
                ) AS total
            FROM
                `project` AS p
            WHERE 
            " . (is_int($projectId) ? 'p.id = :projectId' : 'p.publicId = :projectId') . "
        ";

        $instance = new self();
        $statement = $instance->connection->prepare($query);
        $statement->execute([
            ':projectId'    => is_int($projectId) 
                ? $projectId 
                : UUID::toBinary($projectId),
        ]);
        $result = $statement->fetch();

        if (!$instance->hasData($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Retrieves counts of phase tasks for a project grouped by status and month.
     *
     * This method builds and executes an aggregate query that:
     * - Joins phaseTask -> projectPhase -> project
     * - Groups results by task status and by the YEAR/MONTH of pt.createdAt
     * - Orders results by year ASC, month ASC, and status ASC
     *
     * Parameter handling:
     * - If $projectId is an int, the query filters on p.id
     * - If $projectId is a UUID, the query filters on p.publicId and the UUID is converted to binary (UUID::toBinary)
     *
     * Returned data shape (each row is an associative array):
     * - status: string Task status
     * - year: int YEAR(pt.createdAt)
     * - month: int MONTH(pt.createdAt)
     * - taskCount: int Number of tasks for that status in the given month/year
     *
     * @param int|UUID $projectId Project identifier (database id when int, public UUID otherwise)
     * 
     * @return array|null Array of associative rows described above, or null when no matching data is found
     *
     * @throws \PDOException If the database query fails
     */
    private static function periodicTaskCount(int|UUID $projectId) 
    {
        $query = "
            SELECT 
                YEAR(pt.createdAt) AS year,
                MONTH(pt.createdAt) AS month,
                COUNT(*) AS taskCount
            FROM 
                `phaseTask` AS pt
            INNER JOIN
                `projectPhase` AS pp
            ON
                pp.id = pt.phaseId
            INNER JOIN
                `project` AS p
            ON 
                p.id = pp.projectId
            WHERE 
                " . (is_int($projectId) ? 'p.id' : 'p.publicId') . " = :projectId
            GROUP BY 
                YEAR(pt.createdAt),
                MONTH(pt.createdAt)
            ORDER BY 
                YEAR(pt.createdAt) ASC,
                MONTH(pt.createdAt) ASC
        ";

        $instance = new self();
        $statement = $instance->connection->prepare($query);
        $statement->execute([
            ':projectId'    => is_int($projectId) 
                ? $projectId 
                : UUID::toBinary($projectId),
        ]);
        $result = $statement->fetchAll();

        if (!$instance->hasData($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Retrieves the top workers for a given project based on a weighted scoring algorithm.
     *
     * This method builds and executes a SQL query that:
     * - Joins users to projects, project phases, tasks and task assignments to compute per-worker metrics.
     * - Filters out soft-deleted users (u.deletedAt IS NULL).
     * - Accepts either an integer project ID or a public UUID (converted to binary) to identify the project.
     * - Aggregates:
     *     - totalTasks: distinct tasks assigned to the worker within the project
     *     - totalProjects: distinct projects the worker is associated with (for the given project filter typically 1)
     * - Computes overallScore as a percentage (rounded to 2 decimals) by:
     *     - Assigning base weights to task priorities (high=5.0, medium=3.0, low=1.0).
     *     - Adjusting completed tasks by timeliness (early: 1.2, on-time: 1.0, late: 0.8).
     *     - Scaling on-going tasks by 0.5 and delayed tasks by 0.3.
     *     - Normalizing by the maximum possible weighted sum (priority weight * 1.2) and multiplying by 100.
     * - Only includes workers with totalTasks > 0.
     * - Orders results by overallScore descending and limits to the top 10 workers.
     *
     * Notes:
     * - If $projectId is not an integer, it is treated as a public UUID and converted to binary before binding.
     * - The query returns null when no matching rows are found.
     *
     * @param int|UUID $projectId Project identifier: either numeric primary key (int) or public UUID instance/string
     *
     * @return array|null Returns an indexed array of up to 10 associative arrays with the following keys on success:
     *      - id: int|binary Worker identifier (DB type)
     *      - firstName: string Worker's first name
     *      - lastName: string Worker's last name
     *      - email: string Worker's email address
     *      - totalTasks: int Number of distinct tasks the worker is assigned in the project
     *      - totalProjects: int Number of distinct projects counted for the worker (filtered by project)
     *      - overallScore: float Percentage score (0.00 - 100.00) rounded to 2 decimal places
     *    Returns null if no workers are found for the project.
     *
     * @access private
     * @static
     */
    private static function topWorkersQuery(int|UUID $projectId): ?array
    {
        $query = "
            SELECT 
                ws.id,
                ws.firstName,
                ws.lastName,
                ws.email,
                ws.totalTasks,
                ws.completedTasks,
                ws.overallScore
            FROM (
                SELECT 
                    u.id,
                    u.firstName,
                    u.lastName,
                    u.email,
                    COUNT(DISTINCT ptw.taskId) as totalTasks,
                    (
                        SELECT COUNT(DISTINCT pt2.id)
                        FROM `phaseTask` AS pt2
                        INNER JOIN `phaseTaskWorker` AS ptw2 
                        ON pt2.id = ptw2.taskId
                        WHERE pt2.status = 'completed'
                        AND ptw2.workerId = u.id
                    ) as completedTasks,
                    ROUND(
                        (SUM(
                            CASE 
                                WHEN pt.status = 'completed' THEN
                                    CASE 
                                        WHEN pt.priority = 'high' THEN 5.0
                                        WHEN pt.priority = 'medium' THEN 3.0
                                        WHEN pt.priority = 'low' THEN 1.0
                                        ELSE 1.0
                                    END *
                                    CASE 
                                        WHEN pt.actualCompletionDateTime < pt.completionDateTime THEN 1.2
                                        WHEN pt.actualCompletionDateTime <= DATE_ADD(pt.completionDateTime, INTERVAL 1 DAY) THEN 1.0
                                        ELSE 0.8
                                    END
                                WHEN pt.status = 'onGoing' THEN
                                    CASE 
                                        WHEN pt.priority = 'high' THEN 5.0 * 0.5
                                        WHEN pt.priority = 'medium' THEN 3.0 * 0.5
                                        WHEN pt.priority = 'low' THEN 1.0 * 0.5
                                        ELSE 0.5
                                    END
                                WHEN pt.status = 'delayed' THEN
                                    CASE 
                                        WHEN pt.priority = 'high' THEN 5.0 * 0.3
                                        WHEN pt.priority = 'medium' THEN 3.0 * 0.3
                                        WHEN pt.priority = 'low' THEN 1.0 * 0.3
                                        ELSE 0.3
                                    END
                                ELSE 0
                            END
                        ) / SUM(
                            CASE 
                                WHEN pt.priority = 'high' THEN 5.0 * 1.2
                                WHEN pt.priority = 'medium' THEN 3.0 * 1.2
                                WHEN pt.priority = 'low' THEN 1.0 * 1.2
                                ELSE 1.2
                            END
                        )
                    ) * 100, 2
                    ) as overallScore
                FROM 
                    `user` AS u
                INNER JOIN 
                    `projectWorker` AS pw
                ON 
                    u.id = pw.workerId
                INNER JOIN 
                    `project` AS p 
                ON
                    pw.projectId = p.id
                INNER JOIN 
                    `projectPhase` AS pp 
                ON 
                    p.id = pp.projectId
                INNER JOIN 
                    `phaseTask` AS pt 
                ON 
                    pp.id = pt.phaseId
                INNER JOIN 
                    `phaseTaskWorker` AS ptw 
                ON 
                    pt.id = ptw.taskId AND u.id = ptw.workerId
                WHERE 
                    u.deletedAt IS NULL
                AND 
                    " . (is_int($projectId) ? 'p.id' : 'p.publicId') . " = :projectId
                GROUP BY 
                    u.id, u.firstName, u.lastName, u.email
                HAVING 
                    totalTasks > 0
            ) AS ws
            ORDER BY 
                ws.overallScore DESC
            LIMIT 10
        ";

        $instance = new self();
        $statement = $instance->connection->prepare($query);
        $statement->execute([
            ':projectId'    => is_int($projectId) 
                ? $projectId 
                : UUID::toBinary($projectId),
        ]);
        $result = $statement->fetchAll();

        if (!$instance->hasData($result)) {
            return null;
        }

        return $result;
    }
}