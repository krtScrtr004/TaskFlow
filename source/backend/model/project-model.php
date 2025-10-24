<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\TaskContainer;
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
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause("SELECT * FROM `project`", $whereClause), 
                $options);

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $projects = new ProjectContainer();
            foreach ($result as $row) {
                $projects->add(Project::fromArray($row));
            }
            return $projects;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public static function findFull(int $projectId, array $options = ['workerLimit' => 10,]): mixed
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $instance = new self();
        try {
            $query = "
                SELECT 
                    p.id AS projectId,
                    p.publicId AS projectPublicId,
                    p.name AS projectName,
                    p.description AS projectDescription,
                    p.budget AS projectBudget,
                    p.status AS projectStatus,
                    p.startDateTime AS projectStartDateTime,
                    p.completionDateTime AS projectCompletionDateTime,
                    p.actualCompletionDateTime AS projectActualCompletionDateTime,
                    p.createdAt AS projectCreatedAt,

                    JSON_OBJECT(
                        'managerId', u.id,
                        'managerPublicId', u.publicId,
                        'managerFirstName', u.firstName,
                        'managerMiddleName', u.middleName,
                        'managerLastName', u.lastName,
                        'managerEmail', u.email,
                        'managerProfileLink', u.profileLink,
                        'managerJobTitles', (
                            SELECT JSON_ARRAYAGG(pjt.name)
                            FROM `userJobTitle` AS pjt
                            WHERE pjt.userId = u.id
                        )
                    ) AS projectManager,

                    (
                        SELECT JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'workerId', pw.id,
                                'workerPublicId', pw.publicId,
                                'workerFirstName', pw.firstName,
                                'workerMiddleName', pw.middleName,
                                'workerLastName', pw.lastName,
                                'workerEmail', pw.email,
                                'workerProfileLink', pw.profileLink,
                                'workerJobTitles', (
                                    SELECT JSON_ARRAYAGG(pjt.name)
                                    FROM `userJobTitle` AS pjt
                                    WHERE pjt.userId = pw.id
                                )
                            )
                            LIMIT " . $options['workerLimit'] .
                ")
                        FROM 
                            `projectWorker` AS pw
                        INNER JOIN 
                            `user` AS u ON pw.userId = u.id
                        WHERE 
                            pw.projectId = p.id
                        
                    ) AS projectWorkers,

                    (
                        SELECT COUNT(*)
                        FROM `projectTask` AS pt
                        WHERE pt.projectId = p.id
                        GROUP BY pt.status
                    ) AS projectTaskStatusCounts,

                    (
                        SELECT COUNT(*)
                        FROM `projectTask` AS pt
                        WHERE pt.projectId = p.id
                        GROUP BY pt.priority
                    ) AS projectTaskPriorityCounts
                
                FROM 
                    `project` AS p
                INNER JOIN
                    `user` AS u ON p.managerId = u.id
                WHERE 
                    p.id = :projectId
            ";

            $statement = $instance->connection->prepare($query);
            $statement->execute([':projectId' => $projectId]);
            $result = $statement->fetch();

            // TODO: Process result into ProjectFull object

            return empty($result) ? null : User::fromArray($result);
        } catch (PDOException $th) {
            throw new DatabaseException($th->getMessage());
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
    public function findById(int $projectId): ?Project
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Invalid Project ID.');
        }

        try {
            return self::find('id = :projectId', ['projectId' => $projectId])->get(0) ?? null;
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
    public function findByPublicId(UUID $publicId): ?Project
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
            return self::find('publicId = :publicId', ['publicId' => $binaryUuid])->get(0) ?? null;
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
            return self::find('managerId = :managerId', [':managerId' => $managerId]);
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
     * @return ProjectContainer|null Container with active projects, or null if none found
     * 
     * @throws InvalidArgumentException If managerId is less than 1
     * @throws DatabaseException If a database error occurs during the query
     */
    public static function findManagerActiveProjectsByManagerId(int $managerId): ?ProjectContainer
    {
        if ($managerId < 1) {
            throw new InvalidArgumentException('Invalid manager ID provided.');
        }

        try {
            $projects = self::find(
                'managerId = :managerId AND status != :completedStatus',
                [
                    ':managerId' => $managerId,
                    ':completedStatus' => WorkStatus::COMPLETED->value,
                ],
            );
            return $projects;
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
     * @return ProjectContainer|null Container with active projects for the worker, or null if none found
     * 
     * @throws InvalidArgumentException If the worker ID is less than 1
     * @throws DatabaseException If a database error occurs during the query execution
     */
    public static function findWorkerActiveProjectsByWorkerId(int $workerId): ?ProjectContainer
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
                    ':workerId' => $workerId,
                    ':completedStatus' => WorkStatus::COMPLETED->value,
                ],
            );
            return $projects;
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
                    u.*
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











    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public static function create(mixed $data): void
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of ProjectModel');
        }
    }
}