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

    protected static function appendOptionsToFindQuery(string $query, array $options): string
    {
        if (isset($options['limit']) && is_numeric($options['limit'])) {
            $query .= " LIMIT " . intval($options['limit']);
        }

        if (isset($options['offset']) && is_numeric($options['offset'])) {
            $query .= " OFFSET " . intval($options['offset']);
        }

        if (isset($options['orderBy'])) {
            $query .= " ORDER BY " . $options['orderBy'];
        }

        return $query;
    }

    abstract public static function create(mixed $data): void;

    abstract public static function all(int $offset = 0, int $limit = 10): mixed;

    abstract protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed;



    abstract public function save(): bool;
    abstract public function delete(): bool;
}