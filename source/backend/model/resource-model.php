<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\ResourceContainer;
use App\Dependent\TaskResource;
use App\Exception\DatabaseException;
use PDOException;

class ResourceModel extends Model
{
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?TaskResource
    {
        // TODO: Implement find() method
        return null;
    }

    public static function all(int $offset = 0, int $limit = 10): mixed
    {
        // TODO: Implement all() method
        return null;
    }

    /**
     * Not implemented as there is no use case for creating a single resource independently.
     * 
     * @param mixed $data
     * 
     * @return mixed
     */
    public static function create(mixed $data): mixed
    {
        // Not implemented (No use case)
        return null;
    }

    /**
     * Inserts multiple Resource entries for a given task into the database and updates each
     * Resource in the provided container with the database-assigned identifier.
     *
     * This method prepares a single INSERT statement and reuses it to insert each resource
     * contained in the provided ResourceContainer for the given task ID. After each successful
     * insert it obtains the last inserted ID from the connection and sets it on the corresponding
     * Resource object inside the container.
     *
     * Behavior and side effects:
     * - Prepares and executes an INSERT INTO `task_resource` for each resource in $resources.
     * - Binds and executes parameters per resource: task ID, resource type ID, optional task worker ID,
     *   quantity, unit rate, estimated/actual units, and note.
     * - Updates each Resource in $resources by calling setId() with the connection's lastInsertId().
     * - Does not wrap the multiple inserts in an explicit transaction; partial inserts may occur if an
     *   error happens mid-iteration.
     * - Assumes each element of $resources is a Resource-like object exposing getType()->getId(),
     *   getQuantity(), getUnitRate(), getEstimatedUnit(), and optional getTaskWorkerId(), getActualUnit(),
     *   getNote() methods; passing other types may cause errors.
     *
     * @param int $taskId Identifier of the task to which resources will be associated
     * @param ResourceContainer $resources Container/iterable of Resource instances to insert (mutated in-place)
     *
     * @throws DatabaseException If a PDOException occurs during statement preparation or execution (wrapped)
     *
     * @return ResourceContainer The same $resources container, with each Resource's ID updated to the DB-assigned value
     */
    public static function createMultiple(int $taskId, ResourceContainer $resources): ResourceContainer
    {
        $instance = new self();
        try {
            $query =
                "INSERT INTO `task_resource` (
                    `task_id`,
                    `resource_type_id`,
                    `task_worker_id`,
                    `quantity`,
                    `unit_rate`,
                    `estimated_unit`,
                    `actual_unit`,
                    `note`
                ) VALUES (
                    :taskId,
                    :resourceTypeId,
                    :taskWorkerId,
                    :quantity,
                    :unitRate,
                    :estimatedUnit,
                    :actualUnit,
                    :note
                )";
            $statement = $instance->connection->prepare($query);
            foreach ($resources as $resource) {
                $params = [
                    ':taskId'           => $taskId,
                    ':resourceTypeId'   => $resource->getType()->getId(),
                    ':taskWorkerId'     => $resource?->getTaskWorkerId(),
                    ':quantity'         => $resource->getQuantity(),
                    ':unitRate'         => $resource->getUnitRate(),
                    ':estimatedUnit'    => $resource->getEstimatedUnit(),
                    ':actualUnit'       => $resource?->getActualUnit(),
                    ':note'             => $resource?->getNote()
                ];
                $statement->execute($params);

                // Set ID given by the DB
                $resource->setId($instance->connection->lastInsertId());
            }
            return $resources;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public static function save(array $data): bool
    {
        // TODO: Implement save() method
        return false;
    }

    protected static function delete(mixed $data): bool
    {
        // TODO: Implement delete() method
        return false;
    }
}
