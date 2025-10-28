<?php

namespace App\Model;

use App\Abstract\Model;
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
use App\Exception\ValidationException;
use App\Exception\DatabaseException;
use App\Validator\UuidValidator;
use InvalidArgumentException;
use DateTime;
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
     * Finds and retrieves a project with conditionally included related data.
     *
     * This method performs an optimized database query to fetch a project along with:
     * - Project basic information and manager details with job titles (always included)
     * - Project phases (optional, based on options array)
     * - Project tasks (optional, based on options array)
     * - Project workers with their job titles (optional, based on options array)
     * 
     * The method uses JSON aggregation in SQL to efficiently fetch related data in a single query.
     * By default, only basic project and manager information is fetched. Additional related data
     * (phases, tasks, workers) are only queried when explicitly requested through the options array,
     * improving query performance when the full dataset is not needed.
     *
     * @param UUID $projectId The public UUID of the project to find
     * @param array $options Optional configuration array with following keys:
     *      - phases: bool (default: false) Include project phases if true
     *      - tasks: bool (default: false) Include project tasks if true
     *      - workers: bool (default: false) Include project workers if true
     * 
     * @return Project|null Returns a Project object with requested associated data if found, null if project doesn't exist
     * 
     * @throws DatabaseException If a database error occurs during query execution
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

            // Conditionally add tasks subquery
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
                                'taskCreatedAt', pt.createdAt
                            ) ORDER BY pt.createdAt SEPARATOR ','
                        ), ']')
                        FROM `projectTask` AS pt
                        WHERE pt.projectId = p.id
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
                        status: WorkStatus::from($phase['phaseStatus']),
                        startDateTime: new DateTime($phase['phaseStartDateTime']),
                        completionDateTime: new DateTime($phase['phaseCompletionDateTime'])
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
     * @param int $projectId Numeric project identifier (must be >= 1)
     *
     * @return Project|null The matching Project instance, or null if not found
     *
     * @throws InvalidArgumentException If the provided $projectId is invalid (< 1)
     * @throws DatabaseException If a database error occurs (wraps the underlying PDOException)
     */
    public static function findById(int $projectId): ?Project
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Invalid Project ID.');
        }

        try {
            return self::find('p.id = :projectId', ['projectId' => $projectId])->getItems() ?? null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds a Project by its public UUID.
     *
     * This method validates the provided UUID, converts it to the binary representation
     * used by storage, and attempts to retrieve the first matching Project record.
     * - Validates the $publicId using UuidValidator
     * - Converts the validated UUID to binary via UUID::toBinary
     * - Queries the data store for a Project with the matching binary publicId
     *
     * @param UUID $publicId Public identifier for the project to locate
     *
     * @return Project|null The found Project instance, or null if no matching project exists
     *
     * @throws ValidationException If the provided UUID fails validation (validator errors accessible from the validator)
     * @throws DatabaseException If a database error occurs while performing the lookup (wraps underlying PDO errors)
     */
    public static function findByPublicId(UUID $publicId): ?Project
    {
        $uuidValidator = new UuidValidator();
        $uuidValidator->validateUuid($publicId);
        if ($uuidValidator->hasErrors()) {
            throw new ValidationException(
                'Invalid Project ID',
                $uuidValidator->getErrors()
            );
        }

        $binaryUuid = UUID::toBinary($publicId);
        try {
            return self::find('p.publicId = :publicId', ['publicId' => $binaryUuid])->getItems() ?? null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds all projects managed by a specific manager.
     *
     * This method retrieves projects from the database where the managerId matches
     * the provided ID. It validates the manager ID before executing the query and
     * handles potential database errors.
     *
     * @param int $managerId The ID of the manager whose projects should be retrieved.
     *                       Must be a positive integer greater than 0.
     * 
     * @return ProjectContainer|null Container with projects managed by the specified manager,
     *                               or null if no projects are found.
     * 
     * @throws InvalidArgumentException If the provided manager ID is less than 1.
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    public static function findByManagerId(int $managerId): ?ProjectContainer
    {
        if ($managerId < 1) {
            throw new InvalidArgumentException('Invalid manager ID provided.');
        }

        try {
            return self::find('p.managerId = :managerId', [':managerId' => $managerId]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds projects associated with a given worker (user) ID.
     *
     * This method validates the provided worker ID and queries the database for
     * projects that have an entry in the projectWorker table referencing the worker:
     * - Validates that $workerId is a positive integer (>= 1)
     * - Executes a SELECT with a subquery: id IN (SELECT projectId FROM projectWorker WHERE userId = :workerId)
     * - Uses a prepared/bound parameter (:workerId) to avoid SQL injection
     * - Wraps lower-level PDO exceptions in a DatabaseException
     *
     * @param int $workerId Positive integer ID of the worker whose projects should be retrieved
     *
     * @return ProjectContainer|null ProjectContainer containing the found project(s) or null if none found
     *
     * @throws InvalidArgumentException If $workerId is not a valid positive integer
     * @throws DatabaseException If a database error occurs (wraps the underlying PDOException)
     */
    public static function findByWorkerId(int $workerId): ?ProjectContainer
    {
        if ($workerId < 1) {
            throw new InvalidArgumentException('Invalid worker ID provided.');
        }

        try {
            return self::find(
                'id IN (
                    SELECT projectId 
                    FROM projectWorker 
                    WHERE userId = :workerId
                )',
                [':workerId' => $workerId],
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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
            $projects = self::find(
                'p.managerId = :managerId AND p.status != :completedStatus',
                [
                    ':managerId' => $managerId,
                    ':completedStatus' => WorkStatus::COMPLETED->value,
                ],
                [
                    'limit'     => 1,
                    'orderBy'   => 'createdAt DESC',
                ]
            );
            return $projects->getItems() ?? null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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

        try {
            $projects = self::find(
                'id IN (
                    SELECT projectId 
                    FROM projectWorker 
                    WHERE userId = :workerId
                ) AND status != :completedStatus',
                [
                    ':workerId'         => $workerId,
                    ':completedStatus'  => WorkStatus::COMPLETED->value,
                ],
                [
                    'limit'     => 1,
                    'orderBy'   => 'createdAt DESC',
                ]
            );
            return $projects->getItems() ?? null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds and retrieves all workers associated with a specific project.
     *
     * This method queries the database to fetch all users who are assigned as workers
     * to the specified project by joining the user and projectWorker tables. It returns
     * a WorkerContainer with all matching workers, or null if no workers are found.
     *
     * @param int $projectId The ID of the project to find workers for
     * 
     * @return WorkerContainer|null Container with Worker objects if workers are found,
     *                              null if no workers are associated with the project
     * 
     * @throws InvalidArgumentException If projectId is less than 1
     * @throws DatabaseException If a database error occurs during the query execution
     */
    public static function findWorkersByProjectId(int $projectId): ?WorkerContainer
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    u.id,
                    u.publicId,
                    u.firstName,
                    u.middleName,
                    u.lastName,
                    u.gender,
                    u.email,
                    u.contactNumber,
                    u.profileLink,
                FROM 
                    `user` AS u
                INNER JOIN 
                    `projectWorker` AS pw ON u.id = pw.userId
                WHERE 
                    pw.projectId = :projectId
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':projectId' => $projectId]);
            $result = $statement->fetchAll();

            if (empty($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $workers->add(Worker::fromArray($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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
            return self::find('', [], [
                'offset' => $offset,
                'limit' => $limit,
                'orderBy' => 'createdAt DESC',
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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
            throw new InvalidArgumentException('Expected instance of Project');
        }

        $instance = new self();

        $projectUuid               =   $project->getPublicId() ?? UUID::get();
        $projectName               =   trimOrNull($project->getName());
        $projectDescription        =   trimOrNull($project->getDescription());
        $projectBudget             =   ($project->getBudget()) ?? 0.00;
        $projectStatus             =   $project->getStatus() ?? WorkStatus::PENDING;
        $projectStartDateTime      =   $project->getStartDateTime();
        $projectCompletionDateTime =   $project->getCompletionDateTime();
        $projectPhases             =   $project->getPhases();

        try {
            $instance->connection->beginTransaction();
            
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
                ':publicId'             => UUID::toBinary($projectUuid),
                ':name'                 => $projectName,
                ':description'          => $projectDescription,
                ':budget'               => $projectBudget,
                ':status'               => $projectStatus->value,
                ':startDateTime'        => formatDateTime($projectStartDateTime, DateTime::ATOM),
                ':completionDateTime'   => formatDateTime($projectCompletionDateTime, DateTime::ATOM),
                ':managerId'            => Me::getInstance()->getId(),
            ]);
            $projectId = intval($instance->connection->lastInsertId());

            if ($projectPhases->count() > 0) {
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
                        ':startDateTime'        => formatDateTime($phase->getStartDateTime(), DateTime::ATOM),
                        ':completionDateTime'   => formatDateTime($phase->getCompletionDateTime(), DateTime::ATOM),
                        ':status'               => $phase->getStatus()->value,
                    ]);
                }
            }

            $instance->connection->commit();

            $project->setId($projectId);
            $project->setPublicId($projectUuid);
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
     *      - id: int|string (optional) Internal project ID (takes precedence over publicId)
     *      - publicId: string|UUID (optional) Public project identifier (used if id not present)
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
                $params[':id'] = $data['id'];
            } else {
                $params[':id'] = UUID::toBinary($data['publicId']);
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
                $params[':startDateTime'] = formatDateTime($data['startDateTime'], DateTime::ATOM);
            }

            if (isset($data['completionDateTime'])) {
                $updateFields[] = 'completionDateTime = :completionDateTime';
                $params[':completionDateTime'] = formatDateTime($data['completionDateTime'], DateTime::ATOM);
            }

            if (isset($data['actualCompletionDateTime'])) {
                $updateFields[] = 'actualCompletionDateTime = :actualCompletionDateTime';
                $params[':actualCompletionDateTime'] = $data['actualCompletionDateTime'] !== null 
                    ? formatDateTime($data['actualCompletionDateTime'], DateTime::ATOM) 
                    : null;
            }

            if (!empty($updateFields)) {
                $projectQuery = "UPDATE `project` SET " . implode(', ', $updateFields) . " WHERE " . (isset($data['id']) ? 'id' : 'publicId') . " = :id";
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




    public function delete(): bool
    {
        return true;
    }


}