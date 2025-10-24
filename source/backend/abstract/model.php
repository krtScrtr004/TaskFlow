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

    protected function hasData(array $data): bool
    {
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
        if ($where && $where !== '') {
            $query .= " WHERE " . $where;
        }
        return $query;
    }

    protected function appendOptionsToFindQuery(string $query, array $options): string
    {
        if (isset($options['orderBy'])) {
            $query .= " ORDER BY " . $options['orderBy'];
        }

        if (isset($options['limit']) && is_numeric($options['limit'])) {
            $query .= " LIMIT " . intval($options['limit']);
        }

        if (isset($options['offset']) && is_numeric($options['offset'])) {
            $query .= " OFFSET " . intval($options['offset']);
        }

        return $query;
    }

    abstract public static function create(mixed $data): void;

    abstract public static function all(int $offset = 0, int $limit = 10): mixed;

    abstract protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed;



    abstract public function save(): bool;
    abstract public function delete(): bool;
}