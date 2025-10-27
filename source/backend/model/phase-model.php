<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\PhaseContainer;
use App\Enumeration\WorkStatus;
use App\Dependent\Phase;
use App\Exception\DatabaseException;
use DateTime;
use InvalidArgumentException;
use PDOException;

class PhaseModel extends Model
{
    /**
     * Finds project phases in the database based on provided conditions.
     *
     * This method queries the projectPhase table with a customizable WHERE clause
     * and supports pagination and ordering through options:
     * - Performs a SELECT query with the given where clause
     * - Applies limit, offset, and order by parameters when provided
     * - Returns results as a PhaseContainer of Phase objects
     *
     * @param string $whereClause SQL WHERE clause to filter results (without the "WHERE" keyword)
     * @param array $params Parameters to bind to the prepared statement for the where clause
     * @param array $options Additional query options with following supported keys:
     *      - limit: int Maximum number of records to return
     *      - offset: int Number of records to skip
     *      - orderBy: string ORDER BY clause (without the "ORDER BY" keywords)
     * 
     * @return PhaseContainer|null PhaseContainer containing matching Phase objects, or null if no results found
     * @throws DatabaseException If a database error occurs during the query
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?PhaseContainer
    {
        $instance = new self();
        try {
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause("SELECT * FROM `projectPhase`", $whereClause), 
                $options);
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $phases = new PhaseContainer();
            foreach ($result as $item) {
                $phases->add(Phase::fromArray($item));
            }
            return $phases;
        } catch (PDOException $th) {
            throw new DatabaseException($th->getMessage());
        }
    }

    /**
     * Finds all phases associated with a specific project.
     * 
     * This method retrieves all phases that belong to the specified project ID
     * and returns them as a PhaseContainer object.
     * 
     * @param int $projectId The ID of the project for which to find phases
     * @return PhaseContainer|null A container with all phases belonging to the project,
     *                           or null if no phases were found
     * @throws InvalidArgumentException If the provided project ID is invalid (less than 1)
     * @throws DatabaseException If there is an error in the database operation
     */
    public static function findAllByProjectId(int $projectId): ?PhaseContainer
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Invalid Project ID.');
        }

        try {
            $phases = self::find('projectId = :projectId', ['projectId' => $projectId]);
            return $phases;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves all phases with pagination support.
     *
     * This method fetches all phases from the database with optional pagination parameters.
     * It validates the pagination parameters before executing the query and wraps any
     * database errors in a DatabaseException.
     *
     * @param int $offset The number of records to skip (must be non-negative)
     * @param int $limit The maximum number of records to return (must be at least 1)
     * @return PhaseContainer|null A container with all Phase objects or null if none found
     *
     * @throws InvalidArgumentException When offset is negative or limit is less than 1
     * @throws DatabaseException When a database error occurs during query execution
        */
    public static function all(int $offset = 0, int $limit = 10): ?PhaseContainer
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Invalid offset value.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Invalid limit value.');
        }

        try {
            $phases = self::find('', [], ['offset' => $offset, 'limit' => $limit]);
            return $phases;
        } catch (PDOException $th) {
            throw new DatabaseException($th->getMessage());
        }
    }

    public function save(array $data): bool
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $updateFields = [];
            $params = [':id' => $data['id']];

            if (isset($data['name'])) {
                $updateFields[] = 'name = :name';
                $params[':name'] = trimOrNull($data['name']);
            }

            if (isset($data['description'])) {
                $updateFields[] = 'description = :description';
                $params[':description'] = trimOrNull($data['description']);
            }

            if (isset($data['status'])) {
                $updateFields[] = 'status = :status';
                $params[':status'] = $data['status']->value;
            }

            if (isset($data['startDateTime'])) {
                $updateFields[] = 'startDateTime = :startDateTime';
                $params[':startDateTime'] = formatDateTime($data['startDateTime'], DateTime::ATOM);
            }

            if (isset($data['completionDateTime'])) {
                $updateFields[] = 'completionDateTime = :completionDateTime';
                $params[':completionDateTime'] = formatDateTime($data['completionDateTime'], DateTime::ATOM);
            }

            if (!empty($updateFields)) {
                $projectQuery = "UPDATE `projectPhase` SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $statement = $instance->connection->prepare($projectQuery);
                $statement->execute($params);
            }

            $instance->connection->commit();
            return true;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }









    public function delete(): bool
    {
        return true;
    }

    public static function create(mixed $data): mixed
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of PhaseModel');
        }
        return [];
    }
}