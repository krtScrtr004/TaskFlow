<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\PhaseContainer;
use App\Core\UUID;
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

    /**
     * Creates a Phase instance from an array of data.
     *
     * Note: This method is currently not implemented as there is no use case for it.
     * The creation of Phase instances is handled through other means in the application.
     *
     * @param mixed $data Data that would be used to create a Phase instance (unused)
     * 
     * @return null Returns null as the method is not implemented
     */
    public static function create(mixed $data): null
    {
        // Not Implemented (No use case)
        return null;
    }

    /**
     * Creates multiple phases in a single transaction for improved performance and atomicity.
     *
     * This method allows batch insertion of multiple phases within a single database transaction.
     * If any insertion fails, all changes are rolled back ensuring data consistency.
     * Each phase is inserted individually with its specified fields.
     *
     * @param int $projectId The ID of the project to which all phases belong (required)
     * @param PhaseContainer $phases PhaseContainer with Phase objects. Each Phase object should have:
     *      - name: string (required) The phase name
     *      - description: string (optional) The phase description
     *      - status: WorkStatus (optional) The phase status enum (defaults to PENDING if null)
     *      - startDateTime: DateTime (required) The phase start date/time
     *      - completionDateTime: DateTime (required) The phase completion date/time
     *      - publicId: UUID (optional) Will be generated if not provided
     *
     * @return array Returns an array of created phase IDs and public IDs in the same order as input
     *
     * @throws InvalidArgumentException If PhaseContainer is empty, projectId is invalid, container contains non-Phase objects, or required fields are missing
     * @throws DatabaseException If a database error occurs during any insertion operation
     * 
     * @example
     * $container = new PhaseContainer();
     * $container->add(new Phase(
     *     id: null,
     *     publicId: UUID::get(),
     *     name: 'Planning Phase',
     *     description: 'Initial planning',
     *     status: WorkStatus::PENDING,
     *     startDateTime: new DateTime('2025-11-01'),
     *     completionDateTime: new DateTime('2025-11-15')
     * ));
     * $phaseIds = PhaseModel::createMultiple(1, $container);
     */
    public static function createMultiple(int $projectId, PhaseContainer $phases): array
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID.');
        }

        if ($phases->count() === 0) {
            throw new InvalidArgumentException('PhaseContainer cannot be empty.');
        }

        $instance = new self();
        $createdIds = [];

        try {
            $instance->connection->beginTransaction();

            $insertQuery = "
                INSERT INTO `projectPhase` (
                    projectId,
                    publicId,
                    name,
                    description,
                    startDateTime,
                    completionDateTime,
                    status
                ) VALUES (
                    :projectId,
                    :publicId,
                    :name,
                    :description,
                    :startDateTime,
                    :completionDateTime,
                    :status
                )
            ";
            $statement = $instance->connection->prepare($insertQuery);

            $index = 0;
            foreach ($phases as $phase) {
                if (!($phase instanceof Phase)) {
                    throw new InvalidArgumentException("Item at index {$index} is not a Phase object.");
                }

                // Generate UUID if not provided
                $publicId = $phase->getPublicId() ?? UUID::get();

                // Prepare parameters
                $params = [
                    ':projectId'            => $projectId,
                    ':publicId'             => UUID::toBinary($publicId),
                    ':name'                 => trimOrNull($phase->getName()),
                    ':description'          => trimOrNull($phase->getDescription()),
                    ':startDateTime'        => formatDateTime($phase->getStartDateTime(), DateTime::ATOM),
                    ':completionDateTime'   => formatDateTime($phase->getCompletionDateTime(), DateTime::ATOM),
                    ':status'               => $phase->getStatus() ? $phase->getStatus()->value : WorkStatus::PENDING->value
                ];

                $statement->execute($params);
                $createdIds[] = [
                    'id'        => (int) $instance->connection->lastInsertId(),
                    'publicId'  => $publicId
                ];
                $index++;
            }

            $instance->connection->commit();
            return $createdIds;
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Saves phase data to the database.
     *
     * This method is currently not implemented as there is no use case for saving
     * phase data through this model. The method exists to maintain interface
     * compatibility but will always return false.
     *
     * @param array $data Associative array containing phase data to be saved
     * 
     * @return bool Always returns false as the method is not implemented
     */
    public static function save(array $data): bool
    {
        // Not implemented (No use case)
        return false;
    }

    /**
     * Saves multiple phases in a single transaction for improved performance and atomicity.
     *
     * This method allows batch updating of multiple phases within a single database transaction.
     * If any update fails, all changes are rolled back ensuring data consistency.
     * Each phase in the array is updated individually with its specified fields.
     *
     * @param array $phases Array of phase data arrays. Each phase array should contain:
     *      - id: int (required) The phase ID to update
     *      - name: string (optional) The phase name
     *      - description: string (optional) The phase description
     *      - status: WorkStatus (optional) The phase status enum
     *      - startDateTime: DateTime (optional) The phase start date/time
     *      - completionDateTime: DateTime (optional) The phase completion date/time
     * 
     * @return bool Returns true if all phases were successfully updated
     * 
     * @throws InvalidArgumentException If phases array is empty or any phase is missing an ID
     * @throws DatabaseException If a database error occurs during any update operation
     * 
     * @example
     * PhaseModel::saveMany([
     *     ['id' => 1, 'description' => 'Updated phase 1', 'status' => WorkStatus::IN_PROGRESS],
     *     ['id' => 2, 'description' => 'Updated phase 2'],
     *     ['id' => 3, 'startDateTime' => new DateTime('2025-11-01')]
     * ]);
     */
    public static function saveMultiple(array $phases): bool
    {
        if (empty($phases)) {
            throw new InvalidArgumentException('Phases array cannot be empty.');
        }

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            foreach ($phases as $data) {
                if (!isset($data['id']) && !isset($data['publicId'])) {
                    throw new InvalidArgumentException('Phase ID / Public ID is required for each phase in the array.');
                }

                $updateFields = [];
                $params = [];
                if (isset($data['id'])) {
                    $params[':id'] = $data['id'];
                } else {
                    $params[':id'] = UUID::toBinary($data['publicId']);
                }

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

                // Only execute update if there are fields to update
                if (!empty($updateFields)) {
                    $query = "UPDATE `projectPhase` SET " . implode(', ', $updateFields) . " WHERE " . (isset($data['id']) ? 'id' : 'publicId') . " = :id";
                    $statement = $instance->connection->prepare($query);
                    $statement->execute($params);
                }
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
}