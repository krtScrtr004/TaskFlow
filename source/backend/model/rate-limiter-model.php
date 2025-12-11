<?php

namespace App\Model;

use App\Abstract\Model;
use App\Exception\DatabaseException;
use InvalidArgumentException;
use PDOException;

class RateLimiterModel extends Model
{
    /**
     * Creates a rate limiter record for a given IP and endpoint.
     *
     * Validates the provided data and inserts a new record into the `rateLimiter` table.
     * Behavior:
     * - Expects an array with 'ip' and 'endpoint' keys; throws InvalidArgumentException if missing or empty.
     * - Accepts an optional 'timeWindow' (int, seconds) to determine expiration; defaults to 3600 (1 hour).
     * - Computes expiresAt as time() + timeWindow and stores it as a Unix timestamp.
     * - Performs the insertion using PDO; on PDO errors a DatabaseException is thrown wrapping the original message.
     *
     * @param array $data Associative array containing rate limiter data with the following keys:
     *      - ip: string Required. Client IP address to be rate-limited.
     *      - endpoint: string Required. Endpoint identifier or path to be rate-limited.
     *      - timeWindow: int (optional) Expiration window in seconds; default is 3600 (1 hour).
     *
     * @return bool True on successful insertion.
     *
     * @throws InvalidArgumentException If $data is not an array or required keys are missing/empty.
     * @throws DatabaseException If a PDOException occurs during the database operation.
     */
    public static function create(mixed $data): mixed
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data must be an array.');
        }

        if (!isset($data['ip']) || empty($data['ip'])) {
            throw new InvalidArgumentException('IP address is required.');
        }

        if (!isset($data['endpoint']) || empty($data['endpoint'])) {
            throw new InvalidArgumentException('Endpoint is required.');
        }

        $ip = $data['ip'];
        $endpoint = $data['endpoint'];
        $timeWindow = $data['timeWindow'] ?? 3600; // default 1 hour

        try {
            $instance = new self();

            $expiresAt = time() + $timeWindow;

            $query = "
                INSERT INTO
                    `rate_limiter` (ip, endpoint, expires_at)
                VALUES
                    (:ip, :endpoint, :expiresAt)
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':ip'           => $ip,
                ':endpoint'     => $endpoint,
                ':expiresAt'    => $expiresAt // Unix timestamp
            ]);

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Searches the rateLimiter table for a single entry matching the supplied IP and endpoint.
     *
     * This method:
     * - Validates that $ip and $endpoint are not empty and throws InvalidArgumentException otherwise
     * - Instantiates the model and prepares a SELECT ... LIMIT 1 query against the `rateLimiter` table
     * - Binds the provided $endpoint to :endpoint, executes the query and fetches the first row
     * - Returns the fetched row as an associative array if present, or null when no matching record exists
     *
     * @param string $ip Client IP address to search for
     * @param string $endpoint Endpoint identifier or path to match in the rateLimiter table
     *
     * @return array|null Associative array of the matched rateLimiter record (column => value) if found; null otherwise
     *
     * @throws InvalidArgumentException If $ip or $endpoint is empty
     * @throws DatabaseException If a PDOException occurs during query preparation or execution
     */
    public static function search(string $ip, string $endpoint)
    {
        if (empty($ip)) {
            throw new InvalidArgumentException('IP address cannot be empty.');
        }

        if (empty($endpoint)) {
            throw new InvalidArgumentException('Endpoint cannot be empty.');
        }

        try {
            $instance = new self();

            $query = "
                SELECT 
                    *
                FROM 
                    `rate_limiter`
                WHERE 
                    ip = :ip
                AND
                    endpoint = :endpoint
                LIMIT 1
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':ip'       => $ip,
                ':endpoint' => $endpoint
            ]);
            $result = $statement->fetch();

            return $instance->hasData($result) ? $result : null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Saves or updates a rate limiter record for a given IP and endpoint.
     *
     * This method validates input data, normalizes defaults, and updates the corresponding
     * record in the `rateLimiter` table. Key behaviors:
     * - Validates that $data is an array.
     * - Requires a non-empty 'ip' and 'endpoint'.
     * - Requires 'count' to be an integer.
     * - Uses 'timeWindow' (seconds) to compute an expiresAt Unix timestamp (default: 3600).
     * - Executes an UPDATE query to set count and expiresAt for the record matching ip and endpoint.
     *
     * @param array $data Associative array with the following keys:
     *      - ip: string Client IP address (required).
     *      - endpoint: string Endpoint identifier (required).
     *      - count: int Number of requests/count to set (required).
     *      - timeWindow: int|null Time window in seconds used to compute expiresAt (optional, default 3600).
     *
     * @return bool True on successful update.
     *
     * @throws InvalidArgumentException If $data is not an array, or required keys are missing/invalid:
     *      - when 'ip' or 'endpoint' is missing/empty
     *      - when 'count' is not an integer
     * @throws DatabaseException If a PDOException occurs during database interaction (wraps PDOException).
     */
    public static function save(array $data): bool
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data must be an array.');
        }

        if (!isset($data['ip']) || empty($data['ip'])) {
            throw new InvalidArgumentException('IP address is required.');
        }

        if (!isset($data['endpoint']) || empty($data['endpoint'])) {
            throw new InvalidArgumentException('Endpoint is required.');
        }

        if (!isset($data['count']) || !is_int($data['count'])) {
            throw new InvalidArgumentException('Count must be an integer.');
        }

        $ip = $data['ip'];
        $endpoint = $data['endpoint'];
        $count = $data['count'] ?? 0;
        $timeWindow = $data['timeWindow'] ?? 3600; // default 1 hour

        try {
            $instance = new self();

            $expiresAt = time() + $timeWindow;

            $query = "
                UPDATE
                    `rate_limiter`
                SET
                    count = :count,
                    expires_at = :expiresAt
                WHERE
                    ip = :ip
                AND
                    endpoint = :endpoint
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([
                ':ip'           => $ip,
                ':endpoint'     => $endpoint,
                ':count'        => $count,
                ':expiresAt'    => $expiresAt // Unix timestamp
            ]);

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
    * Not implemented (No use case)
    */
    public static function all(int $offset = 0, int $limit = 10): mixed
    {
        return [];
    }

    /**
    * Not implemented (No use case)
    */
    protected static function delete(mixed $data): bool
    {
        return false;
    }

    /**
    * Not implemented (No use case)
    */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed
    {
        return null;
    }
}