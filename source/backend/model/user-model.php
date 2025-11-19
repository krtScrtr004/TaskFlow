<?php

namespace App\Model;

use App\Abstract\Model;
use App\Core\Connection;
use App\Container\JobTitleContainer;
use App\Core\Me;
use App\Core\UUID;
use App\Entity\User;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use DateTime;
use Exception;
use InvalidArgumentException;
use PDOException;

class UserModel extends Model
{

    /**
     * Finds and retrieves user records from the database with aggregated project statistics.
     *
     * This method executes a complex SQL query to fetch user data along with related job titles and project statistics:
     * - Aggregates job titles using GROUP_CONCAT
     * - Calculates total, completed, cancelled, and terminated project counts for each user
     * - Supports filtering, ordering, grouping, limiting, and offsetting via parameters
     * - Converts jobTitles string to array and attaches additionalInfo with project statistics
     *
     * @param string $whereClause Optional SQL WHERE clause for filtering users
     * @param array $params Parameters for prepared statement and status values:
     *      - :completedStatus: int Status value for completed projects
     *      - :cancelledStatus: int Status value for cancelled projects
     *      - :terminatedStatus: int Status value for terminated project workers
     *      - Additional parameters for filtering (if any)
     * @param array $options Query options:
     *      - limit: int Maximum number of records to return (default: 10)
     *      - offset: int Number of records to skip (default: 0)
     *      - orderBy: string SQL ORDER BY clause (default: 'u.firstName DESC')
     *      - groupBy: string SQL GROUP BY clause (default: 'u.id')
     *
     * @return array|null Array of User instances with attached project statistics, or null if no data found
     *
     * @throws DatabaseException If a database error occurs during query execution
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?array
    {
        $options = [
            'limit'     => $options['limit'] ?? 10,
            'offset'    => $options['offset'] ?? 0,
            'orderBy'   => $options['orderBy'] ?? 'u.firstName DESC',
            'groupBy'   => $options['groupBy'] ?? 'u.id'
        ];

        $instance = new self();
        try {
            Csrf::protect();

            $queryString = "    
                SELECT 
                    u.*,
                    GROUP_CONCAT(ujt.title) AS jobTitles,
                    (
                        SELECT 
                            COUNT(DISTINCT p.id)
                        FROM 
                            `project` AS p
                        LEFT JOIN 
                            `projectWorker` AS pw 
                        ON 
                            p.id = pw.projectId
                        WHERE 
                            p.managerId = u.id
                        OR 
                            pw.workerId = u.id
                    ) AS totalProjects,
                    (
                        SELECT 
                            COUNT(DISTINCT p.id)
                        FROM 
                            `project` AS p
                        LEFT JOIN 
                            `projectWorker` AS pw 
                        ON 
                            p.id = pw.projectId
                        WHERE 
                            (p.managerId = u.id
                        OR 
                            pw.workerId = u.id)
                        AND 
                            p.status = :completedStatus
                    ) AS completedProjects,
                    (
                        SELECT 
                            COUNT(DISTINCT p.id)
                        FROM 
                            `project` AS p
                        LEFT JOIN 
                            `projectWorker` AS pw 
                        ON 
                            p.id = pw.projectId
                        WHERE 
                            pw.workerId = u.id 
                        AND 
                            p.status = :cancelledStatus
                    ) AS cancelledProjectCount,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `projectWorker` AS pw
                        WHERE 
                            pw.workerId = u.id
                        AND 
                            pw.status = :terminatedStatus
                    ) AS terminatedProjectCount
                FROM 
                    `user` AS u
                LEFT JOIN 
                    `userJobTitle` AS ujt 
                ON 
                    u.id = ujt.userId";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause),
            $options);

            $params[':completedStatus'] = WorkStatus::COMPLETED->value;
            $params[':cancelledStatus'] = WorkStatus::CANCELLED->value;
            $params[':terminatedStatus'] = WorkerStatus::TERMINATED->value;

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $users = [];
            foreach ($result as $row) {
                $row['jobTitles'] = explode(',', $row['jobTitles']);
                $row['additionalInfo'] = [
                    'totalProjects'             => (int) $row['totalProjects'] ?? 0,
                    'completedProjects'         => (int) $row['completedProjects'] ?? 0,
                    'cancelledProjectCount'     => (int) $row['cancelledProjectCount'] ?? 0,
                    'terminatedProjectCount'    => (int) $row['terminatedProjectCount'] ?? 0
                ];

                $users[] = User::fromArray($row);
            }
            return $users;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    
    /**
     * Locate and return a User by numeric ID or public UUID.
     *
     * This static factory method resolves a user record by either its numeric primary key
     * (id) or its public identifier (UUID). It performs a lightweight lookup to determine
     * the user's role and then delegates to the appropriate subtype loader:
     * - If the role matches Role::PROJECT_MANAGER, it delegates to ProjectManagerModel::findById(...)
     * - Otherwise it delegates to ProjectWorkerModel::findById(...)
     *
     * Behavior and important details:
     * - Accepts either an integer ID or a UUID object/string as $userId.
     * - If an integer is given, it must be >= 1; otherwise an InvalidArgumentException is thrown.
     * - If a UUID is given, it is converted to binary form (UUID::toBinary) before the database query.
     * - The initial query only selects the id and role from the `user` table to decide which model to delegate to.
     * - If no matching record is found, the method returns null.
     * - Any exceptions thrown during database interaction are propagated.
     *
     * @param int|UUID $userId Numeric user ID or public UUID identifying the user.
     *
     * @return User|null Returns an instance of User (concrete type will be ProjectManagerModel
     *                   or ProjectWorkerModel) when a matching user is found, or null if not found.
     *
     * @throws InvalidArgumentException If an integer $userId is less than 1.
     * @throws Exception For database-related errors or any exceptions thrown by delegated loaders.
     *
     * @see ProjectManagerModel::findById()
     * @see ProjectWorkerModel::findById()
     */
    public static function findById(int|UUID $userId): ?User
    {
        if (is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided.');
        }

        $instance = new self();
        try {
            $searchRole = "SELECT id, role FROM `user` WHERE " . (is_int($userId) ? "id" : "publicId") . " = :userId LIMIT 1";
            $statement = $instance->connection->prepare($searchRole);
            $statement->execute([':userId' => is_int($userId) ? $userId : UUID::toBinary($userId)]);
            $result = $statement->fetch();

            if (!$instance->hasData($result)) {
                return null;
            }

            return ($result['role'] === Role::PROJECT_MANAGER->value)
                ? ProjectManagerModel::findById($userId, null, true)
                : ProjectWorkerModel::findById($userId, null, true);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds a user by their email address.
     *
     * This method searches the database for a user with the specified email address.
     * It limits the result to one user since email addresses are expected to be unique.
     * If no user is found with the given email, null is returned.
     *
     * @param string $email The email address to search for
     * @return User|null User if found, null otherwise
     * @throws DatabaseException If a database error occurs during the operation
     */
    public static function findByEmail(string $email): ?User
    {
        try {
            $result = self::find('email = :email AND deletedAt IS NULL', [':email' => $email], ['limit' => 1]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Checks if email or contact number already exists in the database.
     *
     * This method verifies whether the provided email address or contact number
     * is already registered by another user using a single query. It returns an array 
     * indicating which fields (if any) are duplicates. This is useful for registration 
     * and profile update validation.
     *
     * @param string|null $email Email address to check for duplicates
     * @param string|null $contactNumber Contact number to check for duplicates
     * @param int|UUID|null $excludeUserId User ID to exclude from duplicate check (for updates)
     *
     * @return array Associative array with duplicate status:
     *      - email: bool True if email is duplicate, false otherwise
     *      - contactNumber: bool True if contact number is duplicate, false otherwise
     *      - hasDuplicates: bool True if any field is duplicate, false otherwise
     *
     * @throws DatabaseException If a database error occurs during the query
     */
    public static function hasDuplicateInfo(
        ?string $email = null,
        ?string $contactNumber = null,
        int|UUID|null $excludeUserId = null
    ): array {
        $instance = new self();
        $result = [
            'email' => false,
            'contactNumber' => false,
            'hasDuplicates' => false
        ];

        try {
            // Skip if both fields are empty
            if (!$email && !$contactNumber) {
                return $result;
            }

            // Build single query to check both fields
            $whereConditions = [];
            $params = [];

            if ($email) {
                $whereConditions[] = "email = :email";
                $params[':email1'] = $email;
                $params[':email2'] = $email;
            }

            if ($contactNumber) {
                $whereConditions[] = "contactNumber = :contactNumber";
                $params[':contactNumber1'] = $contactNumber;
                $params[':contactNumber2'] = $contactNumber;
            }

            if ($excludeUserId) {
                $whereConditions[] = is_int($excludeUserId) 
                    ? "id != :excludeUserId" 
                    : "publicId != :excludeUserId";
                $params[':excludeUserId'] = is_int($excludeUserId) 
                    ? $excludeUserId 
                    : UUID::toBinary($excludeUserId);
            }

            $query = "
                SELECT 
                    (CASE WHEN " . ($email ? "email = :email1" : "0") . " THEN 1 ELSE 0 END) as email_duplicate,
                    (CASE WHEN " . ($contactNumber ? "contactNumber = :contactNumber1" : "0") . " THEN 1 ELSE 0 END) as contact_duplicate
                FROM `user`
                WHERE " . implode(" OR ", array_filter([
                    $email ? "email = :email2" : null,
                    $contactNumber ? "contactNumber = :contactNumber2" : null
                ]));

            if ($excludeUserId) {
                $query .= (is_int($excludeUserId) ? " AND id != :excludeUserId" : " AND publicId != :excludeUserId");
            }

            $query .= " LIMIT 1";

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $row = $statement->fetch();

            if ($row) {
                $result['email'] = (bool) $row['email_duplicate'];
                $result['contactNumber'] = (bool) $row['contact_duplicate'];
                $result['hasDuplicates'] = $result['email'] || $result['contactNumber'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves all users with pagination support.
     *
     * This method fetches all users from the database with optional pagination 
     * parameters. It's a wrapper around the find() method with empty criteria.
     *
     * @param int $offset Number of records to skip (for pagination)
     * @param int $limit Maximum number of records to return (default 10)
     * @param bool $includeDeleted Whether to include deleted users in the results (default false)
     * @return array Array of User objects or empty array if no users found
     * 
     * @throws InvalidArgumentException If offset is negative or limit is less than 1
     * @throws DatabaseException When database query fails
     */
    public static function all(int $offset = 0, int $limit = 10, bool $includeDeleted = false): ?array
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Invalid offset value.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Invalid limit value.');
        }

        try {
            $whereClause = $includeDeleted ? '' : 'deletedAt IS NULL';
            return self::find($whereClause, [], ['offset' => $offset, 'limit' => $limit]) ?: null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Searches for users based on provided criteria.
     *
     * This method allows searching users by keyword, role, and worker status, with support for pagination.
     * - Keyword search uses full-text matching on first name, middle name, last name, email, and bio.
     * - Role filter restricts results to users with the specified role.
     * - Worker status filter supports special handling for "UNASSIGNED" status, checking for absence of active work as manager or worker.
     * - Supports pagination via 'limit' and 'offset' options.
     *
     * @param string $key Optional search keyword for full-text search.
     * @param Role|null $role Optional role filter (Role enum).
     * @param WorkerStatus|null $status Optional worker status filter (WorkerStatus enum).
     * @param array $options Optional search options:
     *      - limit: int Maximum number of results to return (default: 10)
     *      - offset: int Number of results to skip (default: 0)
     *
     * @return array|null Array of matching users or null if none found.
     *
     * @throws Exception If an error occurs during search.
     */
    public static function search(
        string $key = '',
        Role|null $role = null,
        WorkerStatus|null $status = null,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]): ?array
    {
        try {
            $where = [];
            $params = [];

            if (trimOrNull($key)) {
                $where[] = "
                    MATCH(u.firstName, u.middleName, u.lastName, u.email, u.bio)
                    AGAINST (:key IN NATURAL LANGUAGE MODE)
                ";
                $params[':key'] = $key;
            }

            if ($role) {
                $where[] = "u.role = :role";
                $params[':role'] = $role->value;
            }

            if ($status) {
                // Special case: UNASSIGNED means no active work as manager OR worker
                if ($status === WorkerStatus::UNASSIGNED) {
                    $where[] = "
                        NOT EXISTS (
                            SELECT 1
                            FROM 
                                `project` AS p
                            WHERE 
                                p.managerId = u.id
                            AND 
                                p.status NOT IN (
                                    :completedStatusUnassigned1, :cancelledStatusUnassigned1
                                )
                        )
                        AND NOT EXISTS (
                            SELECT 1
                            FROM 
                                `projectWorker` AS pw
                            JOIN 
                                `project` AS p ON pw.projectId = p.id
                            WHERE 
                                pw.workerId = u.id
                            AND 
                                p.status NOT IN (
                                    :completedStatusUnassigned2, :cancelledStatusUnassigned2
                                )
                        )
                        AND NOT EXISTS (
                            SELECT 1
                            FROM 
                                `phaseTaskWorker` AS ptw
                            JOIN 
                                `phaseTask` AS pt ON ptw.taskId = pt.id
                            WHERE 
                                ptw.workerId = u.id
                            AND 
                                pt.status NOT IN (
                                    :completedStatusUnassigned3, :cancelledStatusUnassigned3
                                )
                        )
                    ";
                    $params[':completedStatusUnassigned1'] = WorkStatus::COMPLETED->value;
                    $params[':cancelledStatusUnassigned1'] = WorkStatus::CANCELLED->value;
                    $params[':completedStatusUnassigned2'] = WorkStatus::COMPLETED->value;
                    $params[':cancelledStatusUnassigned2'] = WorkStatus::CANCELLED->value;
                    $params[':completedStatusUnassigned3'] = WorkStatus::COMPLETED->value;
                    $params[':cancelledStatusUnassigned3'] = WorkStatus::CANCELLED->value;
                } else {
                    $where[] = "
                        ((EXISTS (
                            SELECT 1
                            FROM 
                                `project` p
                            WHERE 
                                p.managerId = u.id
                            AND 
                                p.status NOT IN (
                                    :completedStatusUnassigned, :cancelledStatusUnassigned
                                )
                        )) OR
                        (EXISTS (
                            SELECT 1
                            FROM 
                                `projectWorker` pw
                            WHERE 
                                pw.workerId = u.id
                            AND 
                                pw.status = :workerStatus1
                        ) OR EXISTS (
                            SELECT 1
                            FROM 
                                `phaseTaskWorker` ptw
                            WHERE 
                                ptw.workerId = u.id
                            AND 
                                ptw.status = :workerStatus2
                        )))
                    ";
                    $params[':completedStatusUnassigned'] = WorkStatus::COMPLETED->value;
                    $params[':cancelledStatusUnassigned'] = WorkStatus::CANCELLED->value;
                    $params[':workerStatus1'] = $status->value;
                    $params[':workerStatus2'] = $status->value;
                }
            }

            // Exclude unconfirmed and deleted users
            $where[] = "u.createdAt IS NOT NULL AND u.deletedAt IS NULL";

            $whereClause = !empty($where) ? implode(' AND ', $where) : '';
            return self::find($whereClause, $params, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates a new user in the database.
     *
     * This method takes a User object and persists it to the database. It handles:
     * - Sanitizing and validating all user fields
     * - Converting null values appropriately
     * - Securely hashing the user's password
     * - Creating related job titles in a transaction
     *
     * The method uses a transaction to ensure that either all data is saved
     * (both user record and job titles) or none at all in case of failure.
     *
     * @param mixed $user The User object to be created in the database
     * @throws InvalidArgumentException If the parameter is not a User instance
     * @throws DatabaseException If any database operation fails
     * @return User
     */
    public static function create(mixed $user): User
    {
        $instance = new self();
        try {
            if (!($user instanceof User)) {
                throw new InvalidArgumentException('Expected instance of User');
            }

            $uuid               =   $user->getPublicId() ?? UUID::get();
            $firstName          =   trimOrNull($user->getFirstName());
            $middleName         =   trimOrNull($user->getMiddleName());
            $lastName           =   trimOrNull($user->getLastName());
            $gender             =   $user->getGender()->value;
            $birthDate          =   $user->getBirthDate(); 
            $role               =   $user->getRole()->value;
            $jobTitles          =   $user->getJobTitles()?->toArray();
            $contactNumber      =   trimOrNull($user->getContactNumber());
            $email              =   trimOrNull($user->getEmail());
            $bio                =   trimOrNull($user->getBio());
            $profileLink        =   trimOrNull($user->getProfileLink());
            $password           =   $user->getPassword();
            
            $instance->connection->beginTransaction();

            // Insert User Data
            $userQuery = "
                INSERT INTO `user` (
                    publicId, 
                    firstName, 
                    middleName, 
                    lastName, 
                    gender, 
                    birthDate, 
                    role, 
                    contactNumber, 
                    email, 
                    bio, 
                    profileLink, 
                    password
                ) VALUES (
                    :publicId, 
                    :firstName, 
                    :middleName, 
                    :lastName, 
                    :gender, 
                    :birthDate, 
                    :role, 
                    :contactNumber, 
                    :email, 
                    :bio, 
                    :profileLink, 
                    :password
                )
            ";
            $statement = $instance->connection->prepare($userQuery);
            $statement->execute([
                ':publicId' => UUID::toBinary($uuid),
                ':firstName' => $firstName,
                ':middleName' => $middleName,
                ':lastName' => $lastName,
                ':gender' => $gender,
                ':birthDate' => formatDateTime($birthDate, DateTime::ATOM),
                ':role' => $role,
                ':contactNumber' => $contactNumber,
                ':email' => $email,
                ':bio' => $bio,
                ':profileLink' => $profileLink,
                ':password' => password_hash($password, PASSWORD_ARGON2ID)
            ]);
            $userId = $instance->connection->lastInsertId();

            // Insert Job Titles, if any
            if (!empty($jobTitles)) {
                $jobTitleQuery = "
                    INSERT INTO `userJobTitle` (userId, title)
                    VALUES (:userId, :title)
                ";
                $jobTitleStatement = $instance->connection->prepare($jobTitleQuery);
                foreach ($jobTitles as $title) {
                    $jobTitleStatement->execute([
                        ':userId' => $userId,
                        ':title' => $title
                    ]);
                }
            }

            $instance->connection->commit();

            $user->setId((int)$userId);
            $user->setPublicId($uuid);
            $user->setPassword(null);
            return $user;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Updates an existing user record in the database with provided data.
     *
     * This method performs an update operation on the user table, supporting updates by either user ID or publicId.
     * It handles the following fields:
     * - firstName, middleName, lastName: Trims and updates name fields
     * - bio: Trims and updates user biography
     * - gender: Updates gender using the enum value
     * - contactNumber: Trims and updates contact number
     * - profileLink: Trims and updates profile link
     * - password: Hashes and updates password using Argon2ID
     * - jobTitles: Updates job titles using the updateJobTitles method
     *
     * All updates are performed within a transaction. If any error occurs, the transaction is rolled back.
     *
     * @param array $data Associative array containing user data to update. Supported keys:
     *      - id: int User ID (required if publicId is not provided)
     *      - publicId: string|binary User public identifier (required if id is not provided)
     *      - firstName: string User's first name
     *      - middleName: string User's middle name
     *      - lastName: string User's last name
     *      - bio: string User's biography
     *      - gender: Gender User's gender (enum)
     *      - contactNumber: string User's contact number
     *      - profileLink: string User's profile link
     *      - password: string User's password (will be hashed)
     *      - jobTitles: array Contains 'toRemove' and 'toAdd' arrays for job titles
     *
     * @throws DatabaseException If a database error occurs
     * @throws InvalidArgumentException If validation fails (e.g., missing ID or publicId)
     * @return bool True on successful update, false otherwise
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
                    throw new InvalidArgumentException('Invalid user ID provided.');
                }

                $params[':id'] = $data['id'];
            } elseif (isset($data['publicId'])) {
                $params[':publicId'] = UUID::toBinary($data['publicId']);
            } else {
                throw new InvalidArgumentException('User ID or Public ID is required.');
            }

            if (isset($data['firstName'])) {
                $updateFields[] = 'firstName = :firstName';
                $params[':firstName'] = trimOrNull($data['firstName']);
            }

            if (isset($data['middleName'])) {
                $updateFields[] = 'middleName = :middleName';
                $params[':middleName'] = trimOrNull($data['middleName']);
            }

            if (isset($data['lastName'])) {
                $updateFields[] = 'lastName = :lastName';
                $params[':lastName'] = trimOrNull($data['lastName']);
            }

            if (isset($data['bio'])) {
                $updateFields[] = 'bio = :bio';
                $params[':bio'] = trimOrNull($data['bio']);
            }

            if (isset($data['gender'])) {
                $updateFields[] = 'gender = :gender';
                $params[':gender'] = $data['gender']->value;
            }

            if (isset($data['birthDate'])) {
                $updateFields[] = 'birthDate = :birthDate';
                $params[':birthDate'] = formatDateTime($data['birthDate']);
            }
            
            if (isset($data['contactNumber'])) {
                $updateFields[] = 'contactNumber = :contactNumber';
                $params[':contactNumber'] = trimOrNull($data['contactNumber']);
            }

            if (isset($data['profileLink'])) {
                $updateFields[] = 'profileLink = :profileLink';
                $params[':profileLink'] = trimOrNull($data['profileLink']);
            }

            if (isset($data['password'])) {
                $updateFields[] = 'password = :password';
                $params[':password'] = password_hash(trimOrNull($data['password']), PASSWORD_ARGON2ID);
            }

            if (isset($data['confirm']) && $data['confirm'] === true) {
                $updateFields[] = 'confirmedAt = :confirmedAt';
                $params[':confirmedAt'] = formatDateTime(new DateTime());
            }

            if (isset($data['delete']) && $data['delete'] === true) {
                $updateFields[] = 'deletedAt = :deletedAt';
                $params[':deletedAt'] = formatDateTime(new DateTime());
            }

            if (!empty($updateFields)) {
                $projectQuery = "UPDATE `user` SET " . implode(', ', $updateFields) . " WHERE id = " . (isset($params[':id']) ? ":id" : "(SELECT id FROM users WHERE publicId = :publicId)") . "";
                $statement = $instance->connection->prepare($projectQuery);
                $statement->execute($params);
            }

            if (!isset($data['delete']) && isset($data['jobTitles'])) {
                self::updateJobTitles(
                    $params[':id'] ?? $data['publicId'],
                    $data['jobTitles']['toRemove'],
                    $data['jobTitles']['toAdd']
                );
            }

            $instance->connection->commit();
            return true;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Updates the job titles associated with a user by adding and/or deleting specified titles.
     *
     * This method performs the following operations:
     * - Deletes job titles from the user if provided in $jobTitlesToDelete.
     * - Adds job titles to the user if provided in $jobTitlesToAdd.
     * - Handles both integer user IDs and UUIDs (publicId).
     * - Skips execution if both $jobTitlesToDelete and $jobTitlesToAdd are empty or null.
     * - Throws an exception if the user ID is invalid.
     * - Uses prepared statements to prevent SQL injection.
     *
     * @param int|UUID $userId The user's unique identifier (integer ID or UUID).
     * @param JobTitleContainer|null $jobTitlesToDelete Container of job titles to be deleted from the user (optional).
     * @param JobTitleContainer|null $jobTitlesToAdd Container of job titles to be added to the user (optional).
     *
     * @throws InvalidArgumentException If an invalid user ID is provided.
     * @throws DatabaseException If a database error occurs during the update.
     * @return void
     */
    private static function updateJobTitles(int|UUID $userId, JobTitleContainer|null $jobTitlesToDelete = null, JobTitleContainer|null $jobTitlesToAdd = null): void
    {
        if (is_int($userId) && $userId < 1) {
            throw new InvalidArgumentException('Invalid user ID provided for job title update.');
        }

        if ($jobTitlesToDelete?->count() === 0 && $jobTitlesToAdd?->count() === 0) {
            return;
        }

        $instance = new self();
        try {        
            if (count($jobTitlesToDelete) > 0) {
                $deleteQuery = "
                    DELETE FROM `userJobTitle`
                    WHERE userId = " . (is_int($userId) ? ":userId" : "(SELECT id FROM users WHERE publicId = :userId)") . "
                    AND title = :title
                ";

                foreach ($jobTitlesToDelete as $title) {
                    $deleteStatement = $instance->connection->prepare($deleteQuery);
                    $deleteStatement->execute([
                        ':userId' => is_int($userId) ? $userId : UUID::toBinary($userId),
                        ':title' => $title
                    ]);
                }
            }

            if (count($jobTitlesToAdd) > 0) {
                $insertQuery = "
                    INSERT INTO
                        `userJobTitle` (userId, title)
                    VALUES (
                        " . (is_int($userId) ? ":userId" : "(SELECT id FROM users WHERE publicId = :userId)") . "   
                        , :title
                    )
                ";

                foreach ($jobTitlesToAdd as $title) {
                    $insertStatement = $instance->connection->prepare($insertQuery);
                    $insertStatement->execute([
                        ':userId' => is_int($userId) ? $userId : UUID::toBinary($userId),
                        ':title' => $title
                    ]);
                }
            }

        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * Deletes a User instance from the data source.
     *
     * This method expects a User object and marks it as deleted by updating its record.
     * Internally, it calls the save method with the user's ID and a delete flag.
     * Throws an InvalidArgumentException if the provided data is not a User instance.
     * Any exceptions during the deletion process are rethrown.
     *
     * @param mixed $data Instance of User to be deleted.
     * 
     * @return bool Returns true if the deletion was successful.
     * 
     * @throws InvalidArgumentException If $data is not an instance of User.
     * @throws Exception If an error occurs during the deletion process.
     */
    public static function delete(mixed $data): bool
    {
        if (!$data instanceof User) {
            throw new InvalidArgumentException('Expected instance of User');
        }

        try {
            $instance = new self();
            $instance->save([
                'id' => $data->getId(),
                'delete' => true
            ]);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Permanently deletes a user record from the database.
     *
     * This method removes the user entry identified by its ID from the `user` table.
     * It expects a valid User instance and throws an exception if the argument is invalid.
     * Any database errors encountered during deletion are wrapped in a DatabaseException.
     *
     * @param mixed $data Instance of User to be deleted.
     * 
     * @throws InvalidArgumentException If $data is not an instance of User.
     * @throws DatabaseException If a database error occurs during deletion.
     * 
     * @return bool Returns true if the deletion was successful.
     */
    public static function hardDelete(mixed $data): bool
    {
        if (!$data instanceof User) {
            throw new InvalidArgumentException('Expected instance of User');
        }

        $instance = new self();
        try {
            $deleteQuery = "DELETE FROM `user` WHERE id = :id";
            $statement = $instance->connection->prepare($deleteQuery);
            $statement->execute([':id' => $data->getId()]);
            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }
}