<?php

namespace App\Model;

use App\Abstract\Model;
use App\Exception\DatabaseException;
use App\Exception\ValidationException;
use PDOException;

class TemporaryLinkModel extends Model
{
    /**
     * Creates a TemporaryLink record from an array of data.
     *
     * This method validates the provided data and inserts or updates a temporary link in the database:
     * - Ensures 'email' is a non-empty string
     * - Ensures 'token' is a non-empty string
     * - Inserts a new record or updates the token if the email already exists
     *
     * @param array $data Associative array containing temporary link data with the following keys:
     *      - email: string User's email address
     *      - token: string Temporary token for the link
     *
     * @throws ValidationException If 'email' or 'token' is missing or invalid
     * @throws DatabaseException If a database error occurs during insertion or update
     *
     * @return bool Returns true on successful creation or update of the temporary link
     */
    public static function create(mixed $data): mixed
	{
        if (!isset($data['email']) || !is_string($data['email']) || !trimOrNull($data['email'])) {
            throw new ValidationException('Invalid email provided for TemporaryLink creation.');
        }

        if (!isset($data['token']) || !is_string($data['token']) || !trimOrNull($data['token'])) {
            throw new ValidationException('Invalid token provided for TemporaryLink creation.');
        }

        try {
            $instance = new self();

            $query = "
                INSERT INTO `temporaryLink` (userEmail, token) 
                VALUES (:email, :token1) 
                ON DUPLICATE KEY UPDATE token = :token2
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':email' => $data['email'],
                ':token1' => $data['token'],
                ':token2' => $data['token']
            ]);
            $statement->execute();
            
            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
	}

    /**
     * Searches for a temporary link record by user email.
     *
     * This method validates the provided email and queries the `temporaryLink` table
     * for a record matching the given email address. If a record is found, it is returned;
     * otherwise, null is returned. Throws a ValidationException if the email is invalid,
     * and a DatabaseException if a database error occurs.
     *
     * @param string $email The email address to search for in the `temporaryLink` table.
     * 
     * @return mixed The found temporary link record as an associative array, or null if not found.
     *
     * @throws ValidationException If the provided email is invalid.
     * @throws DatabaseException If a database error occurs during the search.
     */
    public static function search(string $email): mixed 
    {
        if (!trimOrNull($email)) {
            throw new ValidationException('Invalid email provided for TemporaryLink search.');
        }

        try {
            $instance = new self();

            $query = "
                SELECT * FROM `temporaryLink`
                WHERE userEmail = :email
                LIMIT 1
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':email' => $email]);
            $result = $statement->fetch();

            return $result ?: null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Deletes a temporary link associated with the given email address.
     *
     * This method validates the provided email and attempts to delete the corresponding
     * record from the `temporaryLink` table in the database. If the email is invalid,
     * a ValidationException is thrown. If a database error occurs, a DatabaseException is thrown.
     *
     * @param mixed $email The email address associated with the temporary link to delete.
     *                     Must be a non-empty string.
     *
     * @throws ValidationException If the provided email is invalid.
     * @throws DatabaseException If a database error occurs during deletion.
     *
     * @return bool True if a record was deleted, false otherwise.
     */
    public static function delete(mixed $email): bool
	{
        if (!is_string($email) || !trimOrNull($email)) {
            throw new ValidationException('Invalid email provided for TemporaryLink deletion.');
        }

		try {
            $instance = new self();

            $query = "
                DELETE FROM `temporaryLink`
                WHERE userEmail = :email
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':email' => $email]);

            return $statement->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
	}

    /**
     * Finds temporary link records based on the specified criteria.
     *
     * This method is intended to retrieve temporary link records from the database
     * using a custom WHERE clause and parameters. Currently, it is not implemented
     * as there is no use case for this functionality.
     *
     * @param string $whereClause Optional SQL WHERE clause to filter records.
     * @param array $params Optional array of parameters to bind to the query.
     * @param array $options Optional array of additional query options.
     * 
     * @return mixed Returns null as the method is not implemented.
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed
	{
		// Not implemented (No use case)
		return null;
	}

    /**
     * Retrieves all temporary link records with pagination.
     *
     * This method is intended to fetch a list of temporary link entries from the data source,
     * supporting pagination via offset and limit parameters. Currently, it is not implemented
     * as there is no use case for retrieving all temporary links.
     *
     * @param int $offset The number of records to skip before starting to collect the result set.
     * @param int $limit The maximum number of records to return.
     * 
     * @return mixed Returns the list of temporary link records, or null if not implemented.
     */
	public static function all(int $offset = 0, int $limit = 10): mixed
	{
        // Not implemented (No use case)
		return null;
	}

    /**
     * Saves a temporary link record to the data store.
     *
     * This method is currently not implemented and always returns false.
     * There is no use case for saving temporary links at this time.
     *
     * @param array $data Associative array containing temporary link data with possible keys:
     *      - id: int Temporary link ID
     *      - token: string Unique token for the temporary link
     *      - userId: int Associated user ID
     *      - expiresAt: string|DateTime Expiration timestamp
     *      - createdAt: string|DateTime Creation timestamp
     *      - additionalInfo: array (optional) Additional information related to the link
     *
     * @return bool Always returns false as saving is not implemented
     */
    public static function save(array $data): bool
	{
        // Not implemented (No use case)
		return false;
	}
}
