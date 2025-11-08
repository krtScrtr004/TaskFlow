<?php

namespace App\Model;

use App\Abstract\Model;
use App\Exception\DatabaseException;
use App\Exception\ValidationException;
use PDOException;

class TemporaryLinkModel extends Model
{
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
                INSERT INTO `temporaryLink` (email, token) 
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

    public static function search(string $email): mixed 
    {
        if (!trimOrNull($email)) {
            throw new ValidationException('Invalid email provided for TemporaryLink search.');
        }

        try {
            $instance = new self();

            $query = "
                SELECT * FROM `temporaryLink`
                WHERE email = :email
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

    protected static function delete(mixed $email): bool
	{
        if (!is_string($email) || !trimOrNull($email)) {
            throw new ValidationException('Invalid email provided for TemporaryLink deletion.');
        }

		try {
            $instance = new self();

            $query = "
                DELETE FROM `temporaryLink`
                WHERE email = :email
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute([':email' => $email]);

            return $statement->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
	}

    protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed
	{
		// Not implemented (No use case)
		return null;
	}

	public static function all(int $offset = 0, int $limit = 10): mixed
	{
        // Not implemented (No use case)
		return [];
	}

    public static function save(array $data): bool
	{
		// TODO: Implement logic to save a record
		return false;
	}
}
