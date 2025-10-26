<?php

namespace App\Model;

use App\Abstract\Model;
use App\Core\Connection;
use App\Container\JobTitleContainer;
use App\Core\UUID;
use App\Entity\User;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Exception\DatabaseException;
use App\Middleware\Csrf;
use DateTime;
use InvalidArgumentException;
use PDOException;

class UserModel extends Model
{
    /**
     * Finds a user in the database based on specified conditions.
     *
     * This method retrieves a single user record from the database that matches
     * the provided WHERE clause. It supports pagination through limit and offset options.
     *
     * @param string $whereClause SQL WHERE clause to filter results (without the 'WHERE' keyword)
     * @param array $params Parameters to be bound to the prepared statement
     * @param array $options Additional query options:
     *      - limit: int (optional) Maximum number of records to return
     *      - offset: int (optional) Number of records to skip
     *      - orderBy: string (optional) ORDER BY clause (without the 'ORDER BY' keywords)
     * 
     * @return array|null Array of User if found, null otherwise
     * @throws DatabaseException If there is an error executing the query
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?array
    {
        $instance = new self();
        try {
            Csrf::protect();

            $queryString = "    
                SELECT 
                    u.*,
                    GROUP_CONCAT(ujt.title) AS jobTitles
                FROM `user` u
                LEFT JOIN `userJobTitle` ujt ON u.id = ujt.userId";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause),
            $options);

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $users = [];
            foreach ($result as $row) {
                $row['jobTitles'] = explode(',', $row['jobTitles']);
                $users[] = User::fromArray($row);
            }
            return $users;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds a user by their public ID.
     * 
     * This method searches for a user in the database using the provided UUID.
     * The UUID is converted to binary format for database search.
     * 
     * @param UUID $publicId The public UUID to search for
     * @return User|null The User object if found, null otherwise
     * @throws DatabaseException If a database error occurs during the search
     */
    public static function findByPublicId(UUID $publicId): ?User
    {
        try {
            $result = self::find('publicId = :publicId', [':publicId' => UUID::toBinary($publicId)], ['limit' => 1]);
            return $result ? $result[0] : null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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
            $result = self::find('email = :email', [':email' => $email], ['limit' => 1]);
            return $result ? $result[0] : null;
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
     * @return array Array of User objects or empty array if no users found
     * 
     * @throws InvalidArgumentException If offset is negative or limit is less than 1
     * @throws DatabaseException When database query fails
     */
    public static function all(int $offset = 0, int $limit = 10): ?array
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Invalid offset value.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Invalid limit value.');
        }

        try {
            return self::find('', [], ['offset' => $offset, 'limit' => $limit]) ?: null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
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
                ':birthDate' => $birthDate,
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















    // 

    public function get()
    {
        return [];
    }


    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }
}