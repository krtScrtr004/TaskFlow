<?php

namespace App\Abstract;

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
    protected function __construct()
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
        if ($data === false) {
            return false;
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    return false;
                } else {
                    foreach ($value as $subValue) {
                        if ($subValue !== null && $subValue !== '') {
                            return true;
                        }
                    }
                }
            } elseif ($value !== null && $value !== '') {
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
    protected function appendWhereClause(string $query, string $where): string 
    {
        if ($where && is_string($where) && $where !== '') {
            $query .= " WHERE " . $where;
        } elseif (is_array($where) && !empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "$key = :$key";
            }
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        return $query;
    }

    /**
     * Appends SQL result-modifying clauses (GROUP BY, ORDER BY, LIMIT, OFFSET) to a base query.
     *
     * This method inspects the provided $options array and conditionally appends SQL clauses:
     * - Appends "GROUP BY <value>" when 'groupBy' is present (or when legacy ':options' is present).
     * - Appends "ORDER BY <value>" when 'orderBy' is present (or when legacy ':options' is present).
     * - Determines LIMIT from 'limit' or ':limit' (fallback default 10) and appends "LIMIT <n>" only if the option exists and is numeric.
     * - Determines OFFSET from 'offset' or ':offset' (fallback default 0) and appends "OFFSET <n>" only if the option exists and is numeric.
     * - Numeric limit/offset values are cast to integers before being appended.
     *
     * Notes:
     * - GROUP BY and ORDER BY clauses use the literal value provided in 'groupBy' and 'orderBy' respectively.
     * - Presence of the legacy ':options' flag triggers adding GROUP BY / ORDER BY only if the corresponding explicit key exists for its value.
     * - LIMIT and OFFSET are only added when their respective keys exist and contain numeric values; otherwise defaults are only used for internal calculation.
     *
     * @param string $query   Base SQL query string to which clauses will be appended (e.g. "SELECT * FROM table").
     * @param array  $options Associative array of options with following possible keys:
     *      - groupBy: string SQL fragment for GROUP BY (e.g. "column1, column2")
     *      - orderBy: string SQL fragment for ORDER BY (e.g. "created_at DESC")
     *      - :options: mixed Legacy flag that influences whether GROUP BY / ORDER BY may be considered
     *      - limit: int|string Maximum number of records to return (appended only if present and numeric)
     *      - :limit: int|string Legacy alternative for limit
     *      - offset: int|string Number of records to skip (appended only if present and numeric)
     *      - :offset: int|string Legacy alternative for offset
     *
     * @return string Modified SQL query with appended GROUP BY, ORDER BY, LIMIT and OFFSET clauses as applicable.
     */
    protected function appendOptionsToFindQuery(string $query, array $options): string
    {
        if (isset($options['groupBy']) || isset($options[':options'])) {
            $query .= " GROUP BY " . $options['groupBy'];
        }

        if (isset($options['orderBy']) || isset($options[':options'])) {
            $query .= " ORDER BY " . $options['orderBy'];
        }

        $limit = $options['limit'] ?? $options[':limit'] ?? 10;
        if ((isset($options['limit']) && is_numeric($options['limit'])) || 
            (isset($options[':limit']) && is_numeric($options[':limit']))) {
            $query .= " LIMIT " . (is_int($limit) ? $limit : intval($limit));
        }

        $offset = $options['offset'] ?? $options[':offset'] ?? 0;
        if ((isset($options['offset']) && is_numeric($options['offset'])) ||
            (isset($options[':offset']) && is_numeric($options[':offset']))) {
            $query .= " OFFSET " . (is_int($offset) ? $offset : intval($offset));
        }

        return $query;
    }

    abstract public static function create(mixed $data): mixed;

    abstract public static function all(int $offset = 0, int $limit = 10): mixed;

    abstract protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed;
    
    abstract public static function save(array $data): bool;
    
    abstract static protected function delete(mixed $data): bool;
}