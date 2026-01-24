<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\ResourceContainer;
use App\Core\UUID;
use App\Dependent\TaskResource;
use App\Entity\ResourceType;
use App\Exception\DatabaseException;
use Exception;
use InvalidArgumentException;
use PDOException;

class ResourceModel extends Model
{
    /**
     * Finds and returns a collection of non-labor TaskResource records matching the provided criteria.
     *
     * This method constructs and executes a SELECT query against the task_resource table (aliased as
     * ptr) joined to the phase_task table (aliased as pt). It enforces that returned resources are
     * non-labor by appending "AND ptr.task_worker_id IS NULL" to the provided WHERE clause. Each row
     * includes a JSON-encoded subquery for the associated resource_type (returned as the "type" column).
     *
     * Behavior and side effects:
     * - Accepts an optional SQL WHERE clause fragment, an array of bound parameters, and an options
     *   array to control pagination and ordering.
     * - Appends "AND ptr.task_worker_id IS NULL" to the given WHERE clause to filter out labor
     *   resources (those with a task_worker_id).
     * - Builds the final query, applies ORDER, LIMIT and OFFSET based on $options, prepares and
     *   executes the statement using the instance PDO connection.
     * - If no rows are returned, returns null.
     * - For each result row, decodes the JSON "type" payload, instantiates a partial ResourceType via
     *   ResourceType::createPartial, replaces the raw type data with that object, then creates a
     *   TaskResource partial via TaskResource::createPartial and adds it to a ResourceContainer.
     * - Returns a ResourceContainer populated with TaskResource instances on success.
     * - Catches PDOException and rethrows it as a DatabaseException preserving the original message.
     *
     * Supported $options keys (both colon-prefixed and plain variants are checked):
     * - 'limit' or ':limit'   => int maximum number of rows to return (default 50)
     * - 'offset' or ':offset' => int row offset for pagination (default 0)
     * - 'orderBy' or ':orderBy'=> string ORDER BY clause (default 'pt.start_date_time DESC')
     * - 'groupBy'             => string GROUP BY clause (not used by default)
     *
     * @param string $whereClause SQL WHERE clause fragment to append to the query (without the WHERE keyword)
     * @param array $params       Array of bound parameter values for the prepared statement
     * @param array $options      Associative array of options (see supported keys above)
     *
     * @throws DatabaseException  If a PDOException occurs during query preparation/execution
     *
     * @return ResourceContainer|null A ResourceContainer of TaskResource objects if rows found, or null if none
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?ResourceContainer
    {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? 50,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? 0,
            'groupBy'   => null,
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'pt.start_date_time DESC',
        ];

        $whereClause .= ' AND r.task_worker_id IS NULL'; // Ensure only non-labor resources

        $instance = new self();
        try {
            $queryString =
                "SELECT 
                    r.id,
                    r.quantity,
                    r.unit_rate,
                    r.estimated_unit,
                    r.actual_unit,
                    r.note,
                    (
                        SELECT JSON_OBJECT(
                            'id', rt.id,
                            'name', rt.name,
                            'description', rt.description,
                            'unit', rt.unit,
                            'default_rate', rt.default_rate
                        )
                        FROM 
                            `resource_type` AS rt
                        WHERE 
                            rt.id = r.resource_type_id
                        LIMIT 1
                    ) AS type
                FROM
                    `resource` AS r
                INNER JOIN
                    `task` AS t
                ON
                    t.id = r.task_id";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause),
                $paramOptions
            );

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $results = $statement->fetchAll();

            if (empty($results)) {
                return null;
            }

            $resources = new ResourceContainer();
            foreach ($results as $row) {
                $rawType = json_decode($row['type']);
                $type = ResourceType::createPartial($rawType);

                $row['type'] = $type;
                $resources->add(TaskResource::createPartial($row));
            }

            return $resources;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Finds resources associated with a given task identifier.
     *
     * This method accepts either an integer task ID or a UUID object/string representing a public
     * task identifier. It validates integer IDs, prepares a SQL WHERE clause and parameter binding
     * appropriate for the identifier type, and delegates the actual retrieval to the instance
     * method find(), returning the resulting ResourceContainer or null if nothing is found.
     *
     * Behavior and side effects:
     * - If $taskId is an integer, it must be greater than 0; otherwise an InvalidArgumentException
     *   is thrown.
     * - Builds a WHERE clause that references the numeric task primary key when given an integer,
     *   or a subquery that resolves the numeric id from a public UUID when given a UUID.
     * - Binds ':taskId' to the integer ID or to the binary UUID produced by UUID::toBinary().
     * - Supports pagination via $options['limit'] and $options['offset'], defaulting to 10 and 0.
     * - Delegates retrieval to $this->find($whereClause, $params, $optionParams).
     * - Any exceptions raised during processing are propagated to the caller.
     *
     * @param int|UUID $taskId Integer task PK or UUID public identifier of the task
     * @param array $options Optional associative array of query options:
     *                       - 'limit'  => int (default 10)
     *                       - 'offset' => int (default 0)
     *
     * @throws InvalidArgumentException If $taskId is an integer less than 1
     * @throws Exception Propagates exceptions thrown during query preparation or execution
     *
     * @return ResourceContainer|null The found ResourceContainer instance, or null if no match
     */
    public static function findByTaskId(
        int|UUID $taskId,
        array $options = [
            'limit'     => 10,
            'offset'    => 0
        ]
    ): ?ResourceContainer {
        if ($taskId && is_int($taskId) && $taskId < 1) {
            throw new InvalidArgumentException('Invalid task ID.');
        }

        $instance = new self();
        try {
            $whereClause = 't.id = ' . is_int($taskId)
                ? ':taskId'
                : '(SELECT id FROM `task` WHERE public_id = :taskId)';
            $params = [':taskId' => is_int($taskId) ? $taskId : UUID::toBinary($taskId)];
            $optionParams = [
                'limit'     => $options['limit'] ?? $options[':limit'] ?? 10,
                'offset'    => $options['offset'] ?? $options[':offset'] ?? 0
            ];

            return $instance->find($whereClause, $params, $optionParams);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieve a page of Resource items as a ResourceContainer.
     *
     * This static method validates pagination parameters, prepares query options, and delegates
     * the retrieval to self::find(), returning whatever ResourceContainer (or null) that call
     * produces.
     *
     * Behavior and side effects:
     * - Validates that $offset is non-negative; throws InvalidArgumentException if negative.
     * - Validates that $limit is at least 1; throws InvalidArgumentException if less than 1.
     * - Constructs an options array with 'offset' and 'limit' keys and passes it to self::find().
     * - Returns the ResourceContainer result from self::find(), which may be null if no resources
     *   are found.
     * - Any Exception thrown by self::find() is not swallowed by this method and is rethrown.
     *
     * @param int $offset Zero-based index of the first resource to return (must be >= 0)
     * @param int $limit  Maximum number of resources to return (must be >= 1)
     *
     * @throws InvalidArgumentException If $offset < 0 or $limit < 1
     * @throws Exception                Rethrows exceptions thrown by self::find()
     *
     * @return ResourceContainer|null   A container of resources for the requested page, or null
     */
    public static function all(int $offset = 0, int $limit = 10): ?ResourceContainer
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Invalid offset value.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Invalid limit value.');
        }

        try {
            $paramOptions = [
                'offset'    => $offset,
                'limit'     => $limit,
            ];
            return self::find('', [], $paramOptions);
        } catch (Exception $e) {
            throw $e;
        }
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
                "INSERT INTO `resource` (
                    `public_id`,
                    `task_id`,
                    `resource_type_id`,
                    `task_worker_id`,
                    `quantity`,
                    `unit_rate`,
                    `estimated_unit`,
                    `actual_unit`,
                    `note`
                ) VALUES (
                    :publicId,
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
                    ':publicId'         => UUID::toBinary($resource->getPublicId()),
                    ':taskId'           => $taskId,
                    ':resourceTypeId'   => $resource->getType()->getId(),
                    ':taskWorkerId'     => $resource->getTaskWorkerId(),
                    ':quantity'         => $resource->getQuantity(),
                    ':unitRate'         => $resource->getUnitRate(),
                    ':estimatedUnit'    => $resource->getEstimatedUnit(),
                    ':actualUnit'       => $resource->getActualUnit(),
                    ':note'             => $resource->getNote()
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

    /**
     * Updates an existing Resource record in the database with the provided data.
     *
     * This method constructs an SQL UPDATE query based on the fields present in the $data array.
     * It supports updating quantity, unit rate, estimated unit, actual unit, and note fields.
     * The record to update is identified by either 'id' (integer) or 'public_id' (UUID).
     *
     * Behavior and side effects:
     * - Validates that 'id' in $data is a positive integer if provided; throws
     *   InvalidArgumentException otherwise.
     * - Constructs an UPDATE statement dynamically based on which fields are present in $data.
     * - Binds parameters for each field to be updated.
     * - Executes the prepared statement against the database.
     * - Returns true on successful execution.
     * - Catches and rethrows any PDOException that occurs during execution.
     *
     * @param array $data Associative array of fields to update:
     *                    - 'id' (int): Identifier of the resource to update (required)
     *                    - 'quantity' (float): New quantity value
     *                    - 'unitRate' (float): New unit rate value
     *                    - 'estimatedUnit' (float): New estimated unit value
     *                    - 'actualUnit' (float): New actual unit value
     *                    - 'note' (string): New note value
     *
     * @throws InvalidArgumentException If 'id' is provided but is not a positive integer
     * @throws PDOException If a database error occurs during execution
     *
     * @return bool True if the update was successful
     */
    public static function save(array $data): bool
    {
        if (isset($data['id']) && (!is_int($data['id']) || $data['id'] < 1)) {
            throw new InvalidArgumentException('Invalid Resource ID provided.');
        }

        $instance = new self();
        try {
            $updateFields = [];
            $params = [];

            $params[':id'] = isset($data['id']) 
                ? $data['id'] 
                : UUID::toBinary($data['publicId']);

            if (isset($data['quantity'])) {
                $updateFields[] = '`quantity` = :quantity';
                $params[':quantity'] = $data['quantity'];
            }

            if (isset($data['unitRate'])) {
                $updateFields[] = '`unit_rate` = :unitRate';
                $params[':unitRate'] = $data['unitRate'];
            }

            if (isset($data['estimatedUnit'])) {
                $updateFields[] = '`estimated_unit` = :estimatedUnit';
                $params[':estimatedUnit'] = $data['estimatedUnit'];
            }

            if (isset($data['actualUnit'])) {
                $updateFields[] = '`actual_unit` = :actualUnit';
                $params[':actualUnit'] = $data['actualUnit'];
            }

            if (isset($data['note'])) {
                $updateFields[] = '`note` = :note';
                $params[':note'] = $data['note'];
            }

            if (!empty($updateFields)) {
                $updateQuery = 
                    "UPDATE 
                        `resource` 
                    SET " 
                        . implode(', ', $updateFields) . 
                    " WHERE " 
                        . isset($data['id']) 
                            ? '`id` = :id' 
                            : '`public_id` = :id';
                $statement = $instance->connection->prepare($updateQuery);
                $statement->execute($params);
            }

            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    protected static function delete(mixed $data): bool
    {
        // TODO: Implement delete() method
        return false;
    }
}
