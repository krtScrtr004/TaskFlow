<?php

namespace App\Abstract;

use PDO;
use App\Core\Connection;

abstract class Model
{
    protected PDO $connection;

    protected function __construct()
    {
        $this->connection = Connection::getInstance();
    }

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
    
    abstract static protected function delete(): bool;
}