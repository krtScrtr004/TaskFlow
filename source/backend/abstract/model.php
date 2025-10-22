<?php

namespace App\Abstract;

use PDO;
use App\Core\Connection;

abstract class Model {
    protected PDO $connection;

    protected function __construct() {
        $this->connection = Connection::getInstance();
    }

    abstract public static function find($id): ?self;
    abstract public static function all(): mixed;
    abstract public static function create(mixed $data): void;

    abstract public function save(): bool;
    abstract public function delete(): bool;

    // public function fill(array $data): void; 
}