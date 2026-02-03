<?php

namespace App\Abstract;

use InvalidArgumentException;
use PDO;
use App\Core\Connection;

abstract class Model
{
    protected PDO $connection;

    /**
     * Initializes the model with a shared database connection.
     *
     * The protected constructor obtains the Connection singleton and assigns it
     * to the model instance so all models share the same connection resource.
     * It is protected to enforce controlled instantiation via subclasses or
     * factory methods.
     *
     * @see Connection::getInstance()
     *
     * @throws \RuntimeException If the Connection instance cannot be retrieved.
     */
    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    /**
     * Determines whether the given input contains any meaningful (non-empty) data.
     *
     * This method accepts either an array or boolean and inspects the contents to decide
     * if there is any data worth considering:
     * - If $data is boolean false, the method immediately returns false.
     * - If $data is an array, each top-level element is examined:
     *     - If the element is an array:
     *         - An empty sub-array is considered empty.
     *         - Otherwise each sub-value is checked; if any sub-value is not null and not
     *           an empty string (''), the method returns true.
     *     - If the element is not an array and is not null and not an empty string (''), the method returns true.
     * - If no qualifying value is found, the method returns false.
     *
     * Notes:
     * - The checks for emptiness are strict against null and the empty string only. Values like 0, 0.0, or false
     *   (as scalar values) are considered present data unless they are inside an empty array or are equal to ''/null.
     * - Arrays composed solely of nulls and/or empty strings are treated as empty.
     *
     * @param array|bool $data Array of values to inspect (elements may be scalars or arrays) or boolean false
     * @return bool True if at least one non-null, non-empty-string value exists; otherwise false
     */
    protected function hasData(array|bool $data): bool
    {
        if (\is_bool($data) && $data === false) return false;

        foreach ($data as $value) {
            if (\is_array($value)) {
                if (empty($value)) return false;

                foreach ($value as $subValue) {
                    if ($subValue !== null && $subValue !== '') return true;
                }
            } elseif ($value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Appends a WHERE clause to an SQL query string.
     *
     * This method accepts either a string or an associative array for the $where argument:
     * - If $where is a non-empty string, it is appended verbatim prefixed by " WHERE ".
     * - If $where is a non-empty associative array, each entry is converted to a condition
     *   of the form "key = :key" and conditions are joined with " AND ".
     *
     * Behavior notes:
     * - If $where is empty or falsy, the original $query is returned unchanged.
     * - For array input, array keys are used as column identifiers and as PDO-style named
     *   parameter placeholders (":key"). The method does not bind values — binding must be
     *   performed separately to avoid SQL injection.
     * - The method does not validate or quote column names; ensure keys are safe before use.
     *
     * @param string $query SQL query string to append the WHERE clause to.
     * @param string|array $where Either a raw WHERE clause string or an associative array
     *      of column => value pairs to convert into named-parameter conditions.
     * @return string The SQL query with the appended WHERE clause, or the original query
     *      if no conditions were provided.
     */
    protected function appendWhereClause(string $query, string|array $where): string 
    {
        if ($where && \is_string($where) && $where !== '') {
            $query .= " WHERE " . $where;
        } elseif (\is_array($where) && !empty($where)) {
            $conditions = [];
            foreach (array_keys($where) as $key) {
                $conditions[] = "$key = :$key";
            }
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        return $query;
    }

    /**
     * Appends SQL GROUP BY, ORDER BY, LIMIT and OFFSET clauses to a base query string
     * according to provided options.
     *
     * This method inspects the $options array for ordering, grouping and paging
     * instructions and appends the corresponding SQL fragments (with leading spaces)
     * to the given $query. Keys are checked in both plain and namespaced forms
     * (e.g. 'groupBy' and ':groupBy').
     *
     * Behavior and side effects:
     * - Determines GROUP BY from trimOrNull($options['groupBy']) or trimOrNull($options[':groupBy'])
     *   and appends " GROUP BY <value>" only if a non-null value is returned.
     * - Determines ORDER BY from trimOrNull($options['orderBy']) or trimOrNull($options[':orderBy'])
     *   and appends " ORDER BY <value>" only if a non-null value is returned.
     * - Determines LIMIT from (int)$options['limit'] or (int)$options[':limit'], defaulting to 10.
     *   The value is cast to int; if the resulting limit is less than 1 an InvalidArgumentException is thrown.
     * - Determines OFFSET from $options['offset'] or $options[':offset'], defaulting to 0.
     *   The value is cast to int; if the resulting offset is less than 1 an InvalidArgumentException is thrown.
     * - Appends " LIMIT <limit>" and " OFFSET <offset>" using the integer values.
     * - This method does NOT perform SQL value escaping or validation of the GROUP BY / ORDER BY
     *   expressions — callers must ensure values are safe to interpolate into SQL to avoid injection.
     *
     * @param string $query   Base SQL query to which clauses will be appended.
     * @param array  $options Associative array of options. Recognized keys:
     *                        - 'groupBy' or ':groupBy' => string|null
     *                        - 'orderBy' or ':orderBy' => string|null
     *                        - 'limit'   or ':limit'   => int (default 10)
     *                        - 'offset'  or ':offset'  => int (default 0)
     *
     * @throws InvalidArgumentException If the resolved limit is less than 1 or offset is less than 0.
     *
     * @return string The query string with appended clauses.
     */
    protected function appendOptionsToFindQuery(string $query, array $options): string
    {
        $groupBy = trimOrNull($options['groupBy']) ?? trimOrNull($options[':groupBy']);        
        if ($groupBy && $groupBy !== '') $query .= " GROUP BY " . $groupBy;

        $orderBy = trimOrNull($options['orderBy']) ?? trimOrNull($options[':orderBy']);
        if ($orderBy && $orderBy !== '') $query .= " ORDER BY " . $orderBy;

        $limit = (int) ($options['limit'] ?? $options[':limit'] ?? 10);
        if ($limit < 1) throw new InvalidArgumentException('Invalid limit value');
        $query .= " LIMIT " . $limit;

        $offset = (int) ($options['offset'] ?? $options[':offset'] ?? 0);
        if ($offset < 0) throw new InvalidArgumentException('Invalid offset value');
        $query .= " OFFSET " . $offset;

        return $query;
    }

    abstract protected function find(string $whereClause = '', array $params = [], array $options = []): mixed;

    abstract public function create(mixed $data): mixed;

    abstract public function all(int $offset = 0, int $limit = 10): mixed;

    abstract public function save(array $data): bool;
    
    abstract protected function delete(mixed $data): bool;
}