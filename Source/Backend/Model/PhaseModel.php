<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\PhaseContainer;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Enumeration\WorkStatus;
use App\Entity\Phase;
use App\Entity\Task;
use App\Exception\DatabaseException;
use DateTime;
use Exception;
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
    protected function find(string $whereClause = '', array $params = [], array $options = []): PhaseContainer|null
    {
        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? null,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? null,
            'orderBy'   => $options[':orderBy'] ?? $options['orderBy'] ?? 'start_date_time ASC',
        ];

        try {
            $queryString =
                "SELECT 
                    ph.id,
                    ph.public_id,
                    ph.name,
                    ph.description,
                    ph.status,
                    phb.budget,
                    phb.contingency_rate,
                    phb.note,
                    ph.start_date_time,
                    ph.completion_date_time,
                    ph.actual_completion_date_time,
                    ph.created_at,
                    ph.updated_at
                FROM 
                    `phase` AS ph
                INNER JOIN 
                    `phase_budget` AS phb
                ON
                    phb.phase_id = ph.id";

            $query = $this->appendOptionsToFindQuery(
                $this->appendWhereClause($queryString, $whereClause),
                $paramOptions
            );
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$this->hasData($result)) return null;

            $phases = new PhaseContainer();
            foreach ($result as $item) {
                $item['budgetNote'] = $item['note'];
                $phases->add(Phase::createPartial($item));
            }
            return $phases;
        } catch (PDOException $th) {
            throw new DatabaseException($th->getMessage());
        }
    }

    /**
     * Finds a Phase instance by its ID or public UUID.
     *
     * This method retrieves a Phase from the database using either its integer ID or its public UUID.
     * - If an integer is provided, it searches by the 'id' column.
     * - If a UUID is provided, it searches by the 'publicId' column after converting the UUID to binary.
     * - Throws InvalidArgumentException if the provided ID is invalid.
     * - Throws DatabaseException if a PDO error occurs during the query.
     *
     * @param int|UUID $phaseId The Phase identifier, either as an integer ID or a UUID object.
     * 
     * @return Phase|null The found Phase instance, or null if not found.
     *
     * @throws InvalidArgumentException If the provided Phase ID is invalid.
     * @throws DatabaseException If a database error occurs.
     */
    public function findById(int|UUID $phaseId): ?Phase
    {
        if (!$phaseId) throw new InvalidArgumentException('Invalid phase ID provided');

        try {
            $whereClause = is_int($phaseId)
                ? 'id = :phaseId'
                : 'public_id = :phaseId';
            $params = [
                'phaseId' => is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId)
            ];

            $options = ['limit' => 1];

            return self::find($whereClause, $params, $options)->first();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds the ongoing Phase for a given Project ID.
     *
     * This method retrieves the first Phase instance with an "ONGOING" status
     * associated with the specified project. It supports both integer and UUID
     * project identifiers:
     * - If an integer is provided, it is used directly as the project ID.
     * - If a UUID is provided, it is converted to binary and matched against the project's publicId.
     *
     * Throws an InvalidArgumentException if the project ID is invalid (less than 1).
     * Throws a DatabaseException if a PDO error occurs during the query.
     *
     * @param int|UUID $projectId The project identifier, either as an integer or UUID.
     * 
     * @return Phase|null The ongoing Phase instance if found, or null if none exists.
     *
     * @throws InvalidArgumentException If the project ID is invalid.
     * @throws DatabaseException If a database error occurs.
     */
    public function findOnGoingByProjectId(int|UUID $projectId): ?Phase
    {
        if ($projectId < 1) throw new InvalidArgumentException('Invalid project ID provided');

        try {
            $whereClause = is_int($projectId)
                ? 'project_id = :projectId'
                : 'project_id = (SELECT id FROM `project` WHERE public_id = :projectId)';

            $whereClause .= " AND status = :status AND start_date_time <= NOW()";

            $params = [
                'projectId' => is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId),
                'status' => WorkStatus::ONGOING->value
            ];

            $options = ['limit' => 1];

            return self::find($whereClause, $params, $options)->first();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds phases by project ID and schedule boundaries.
     *
     * This method retrieves phase records that match the given project ID and fall within the specified start and/or completion date boundaries.
     * - Validates that the project ID is a positive integer or a UUID.
     * - Requires at least one of start_date_time or completion_date_time to be provided.
     * - Converts project_id to binary if it is a UUID.
     * - Formats start_date_time and completion_date_time for query parameters.
     * - Constructs a WHERE clause based on provided parameters.
     *
     * @param int|UUID $projectId The project identifier (integer or UUID).
     * @param DateTime|null $startDateTime The lower boundary for phase start date (inclusive).
     * @param DateTime|null $completionDateTime The upper boundary for phase completion date (inclusive).
     * 
     * @throws InvalidArgumentException If project_id is invalid or both date boundaries are missing.
     * 
     * @return self[]|null Array of phase instances matching the criteria, or null if an error occurs.
     */
    public function findByScheduleBoundary(
        int|UUID $projectId,
        DateTime|null $startDateTime,
        DateTime|null $completionDateTime,
    ): PhaseContainer|null {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID provided');
        if (!$startDateTime && !$completionDateTime)
            throw new InvalidArgumentException('At least one of start date or completion date must be provided');

        try {
            $where = [];
            $params = [];

            if ($startDateTime) {
                $where[] = 'start_date_time >= :startDateTime';
                $params[':startDateTime'] = formatDateTime($startDateTime);
            }

            if ($completionDateTime) {
                $where[] = 'completion_date_time <= :completionDateTime';
                $params[':completionDateTime'] = formatDateTime($completionDateTime);
            }

            $where[] = 'project_id = :projectId';
            $params[':projectId'] = is_int($projectId)
                ? $projectId
                : UUID::toBinary($projectId);

            $whereClause = implode(' AND ', $where);

            return self::find($whereClause, $params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all phases associated with a given project ID, optionally including related tasks.
     *
     * This method fetches project phases from the database by either internal integer ID or public UUID.
     * If $includeTasks is true, each phase will include a list of its associated tasks as a JSON array.
     * The method converts raw database results into Phase objects, and if tasks are included, into Task objects as well.
     *
     * @param int|UUID $projectId The internal integer ID or public UUID of the project.
     * @param bool $includeTasks Whether to include associated tasks for each phase.
     * 
     * @throws InvalidArgumentException If the provided project ID is invalid.
     * @throws DatabaseException If a database error occurs during retrieval.
     *
     * @return PhaseContainer|null A container of Phase objects for the project, or null if no phases are found.
     */
    public function findByProjectId(
        int|UUID $projectId, 
        array $options = [
            'limit'   => 10,
            'offset'  => 0
        ]
    ): PhaseContainer|null {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID provided');

        $paramOptions = [
            'limit'     => $options[':limit'] ?? $options['limit'] ?? null,
            'offset'    => $options[':offset'] ?? $options['offset'] ?? null,
            'orderBy'   => 'ph.start_date_time ASC',
        ];

        try {
            $instance = new self();

            $queryString =
                "SELECT 
                    ph.*,  
                    phb.budget,
                    phb.contingency_rate,
                    phb.note
                FROM 
                    `phase` AS ph
                INNER JOIN 
                    `phase_budget` AS phb
                ON
                    phb.phase_id = ph.id
                INNER JOIN
                    `project` AS p
                ON 
                    ph.project_id = p.id
                WHERE 
                    " . (\is_int($projectId) ? 'p.id = :projectId' : 'p.public_id = :projectId') . "";

            $query = $this->appendOptionsToFindQuery($queryString, $paramOptions);
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':projectId' => \is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId)
            ]);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) return null;

            $phases = new PhaseContainer();
            foreach ($result as $item) {
                // Populate tasks if requested
                $taskContainer = new TaskContainer();

                $item['budgetNote'] = $item['note'];
                $item['tasks'] = null; // Clear raw tasks data
                $phase = Phase::createPartial($item);
                $phases->add($phase);
            }

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
    public function all(int $offset = 0, int $limit = 10): PhaseContainer|null
    {
        if ($offset < 0) throw new InvalidArgumentException('Invalid offset value');
        if ($limit < 1) throw new InvalidArgumentException('Invalid limit value');

        try {
            $phases = self::find(
                '',
                [],
                [
                    'offset'    => $offset,
                    'limit'     => $limit
                ]
            );
            return $phases;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function create(int|UUID $projectId, Phase|PhaseContainer $phase): Phase|PhaseContainer
    {
        if (\is_int($projectId) && $projectId < 1) 
            throw new InvalidArgumentException('Invalid project ID provided');

        // Allow passing a single Phase without wrapping
        $isBatch = $phase instanceof PhaseContainer;
        $phases = $isBatch ? $phase : new PhaseContainer([$phase]);
        if ($phases->count() === 0) throw new InvalidArgumentException('PhaseContainer cannot be empty');

        try {
            $projectPhaseQuery =
                "INSERT INTO `phase` (
                    project_id,
                    public_id,
                    name,
                    description,
                    start_date_time,
                    completion_date_time,
                    status
                ) VALUES (
                    " . (\is_int($projectId) 
                            ? ":projectId" 
                            : "(SELECT id FROM project WHERE id = :projectId)") . ",
                    :publicId,
                    :name,
                    :description,
                    :startDateTime,
                    :completionDateTime,
                    :status
                )";
            $phaseStatement = $this->connection->prepare($projectPhaseQuery);
            foreach ($phases as &$item) {
                $phaseStatement->execute([
                    ':projectId'            => \is_int($projectId) 
                        ? $projectId 
                        : UUID::toBinary($projectId),
                    ':publicId'             => UUID::toBinary($item->getPublicId()),
                    ':name'                 => $item->getName(),
                    ':description'          => $item->getDescription(),
                    ':startDateTime'        => formatDateTime($item->getStartDateTime()),
                    ':completionDateTime'   => formatDateTime($item->getCompletionDateTime()),
                    ':status'               => $item->getStatus()->value,
                ]);

                // Create budget record
                $this->createBudget($item->getId(), [
                    'budget'            => $item->getBudget(),
                    'contingencyRate'   => $item->getContingencyRate(),
                    'budgetNote'        => $item->getBudgetNote(),
                ]);

                $item->setId((int) $this->connection->lastInsertId());
            }

            return $isBatch ? $phases : $phases->first();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    private function createBudget(int|UUID $phaseId, array $data): void
    {
        if (\is_int($phaseId) && $phaseId < 1) throw new InvalidArgumentException('Invalid phase ID provided');
        if (empty($data)) return;

        try {
            $projectPhaseBudgetQuery =
                "INSERT INTO `phase_budget` (
                    phase_id,
                    budget,
                    contingency_rate,
                    note
                ) VALUES (
                    " . (\is_int($phaseId) 
                            ? ":phaseId" 
                            : "(SELECT id FROM `phase` WHERE public_id = :phaseId)") . ",
                    :budget,
                    :contingencyRate,
                    :note
                )";

            $budgetStatement = $this->connection->prepare($projectPhaseBudgetQuery);
            $budgetStatement->execute([
                ':phaseId'         => \is_int($phaseId)
                    ? $phaseId
                    : UUID::toBinary($phaseId),
                ':budget'          => $data['budget'] ?? 0.00,
                ':contingencyRate' => $data['contingencyRate'] ?? 0.00,
                ':note'            => trimOrNull($data['budgetNote'] ?? null),
            ]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function save(array $phases): bool
    {
        if (empty($phases)) throw new InvalidArgumentException('Phases array cannot be empty');

        // Allow passing a single phase update item without wrapping
        $isBatch = isAssociativeArray($phases) ? false : true;
        if (!$isBatch) $phases = [$phases];

        try {
            foreach ($phases as $item) {
                if (!\is_array($item))
                    throw new InvalidArgumentException('Each phase update item must be an array');

                if (isset($item['id']) && \is_int($item['id']) && $item['id'] < 1)
                    throw new InvalidArgumentException('Invalid phase ID provided');

                $phaseUpdateFields = [];
                $projectPhaseParams = [];

                $projectPhaseParams[':id'] = (isset($item['id']))
                    ? $item['id']
                    : ($item['publicId'] instanceof UUID
                        ? UUID::toBinary($item['publicId'])
                        : UUID::toBinary(UUID::fromString($item['publicId'])));

                if (isset($item['name'])) {
                    $phaseUpdateFields[] = 'name = :name';
                    $projectPhaseParams[':name'] = trimOrNull($item['name']);
                }

                if (isset($item['description'])) {
                    $phaseUpdateFields[] = 'description = :description';
                    $projectPhaseParams[':description'] = trimOrNull($item['description']);
                }

                if (isset($item['status'])) {
                    $phaseUpdateFields[] = 'status = :status';
                    $projectPhaseParams[':status'] = $item['status']->value;
                }

                if (isset($item['startDateTime'])) {
                    $phaseUpdateFields[] = 'start_date_time = :startDateTime';
                    $projectPhaseParams[':startDateTime'] = formatDateTime($item['startDateTime']);
                }

                if (isset($item['completionDateTime'])) {
                    $phaseUpdateFields[] = 'completion_date_time = :completionDateTime';
                    $projectPhaseParams[':completionDateTime'] = formatDateTime($item['completionDateTime']);
                }

                // Only execute update if there are fields to update
                if (!empty($phaseUpdateFields)) {
                    $query = "
                        UPDATE 
                            `phase` 
                        SET 
                            " . implode(', ', $phaseUpdateFields) . " 
                        WHERE 
                            " . (isset($item['id']) 
                                ? 'id' 
                                : 'public_id') . " 
                            = :id";
                    $statement = $this->connection->prepare($query);
                    $statement->execute($projectPhaseParams);
                }

                // Budget update 
                $this->saveBudget($item['id'] ?? UUID::tryFromString($item['publicId']), $item);
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    private function saveBudget(int|UUID $phaseId, array $data)
    {
        if (\is_int($phaseId) && $phaseId < 1) throw new InvalidArgumentException('Invalid phase ID provided');
        if (empty($data)) return;

        try {
            $phaseBudgetUpdateFields = [];
            $phaseBudgetParams = [];

            $phaseBudgetParams[':id'] = (\is_int($phaseId))
                ? $phaseId
                : ($phaseId instanceof UUID
                    ? UUID::toBinary($phaseId)
                    : UUID::toBinary(UUID::fromString($phaseId)));

            if (isset($data['budget'])) {
                $phaseBudgetUpdateFields[] = 'budget = :budget';
                $phaseBudgetParams[':budget'] = $data['budget'];
            }

            if (isset($data['contingencyRate'])) {
                $phaseBudgetUpdateFields[] = 'contingency_rate = :contingencyRate';
                $phaseBudgetParams[':contingencyRate'] = $data['contingencyRate'];
            }

            if (isset($data['budgetNote'])) {
                $phaseBudgetUpdateFields[] = 'note = :budgetNote';
                $phaseBudgetParams[':budgetNote'] = trimOrNull($data['budgetNote']);
            }

            if (!empty($phaseBudgetUpdateFields)) {
                $budgetQuery = "
                        UPDATE 
                            `phase_budget` 
                        SET 
                            " . implode(', ', $phaseBudgetUpdateFields) . " 
                        WHERE 
                            phase_id = " . (\is_int($phaseId) 
                                ? ':id' 
                                : '(SELECT id FROM `phase` WHERE public_id = :id)');
                $budgetStatement = $this->connection->prepare($budgetQuery);
                $budgetStatement->execute($phaseBudgetParams);
            }
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all tasks associated with a specific phase.
     *
     * This method fetches tasks for the given phase ID by delegating to TaskModel.
     *
     * @param int|UUID $phaseId The phase identifier (integer ID or UUID)
     * @param array $options Pagination options:
     *      - offset: int (default 0) Number of records to skip
     *      - limit: int (default 10) Maximum number of records to return
     * 
     * @return TaskContainer|null Container with tasks for the phase, or null if none found
     * 
     * @throws InvalidArgumentException If the provided phase ID is invalid
     * @throws DatabaseException If a database error occurs
     */
    public function getTasks(int|UUID $phaseId, array $options = []): ?TaskContainer
    {
        if (\is_int($phaseId) && $phaseId < 1) throw new InvalidArgumentException('Invalid phase ID provided');

        try {
            $taskModel = new TaskModel();
            return $taskModel->findByPhaseId($phaseId, $options);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes a phase entity.
     *
     * This method is currently not implemented as there is no use case for deleting a phase.
     * Always returns false.
     * 
     * @param mixed $data Data that would be used to delete a phase (unused)
     *
     * @return bool Always returns false to indicate deletion is not supported.
     */
    public function delete(mixed $data): bool
    {
        // Not implemented (No use case)
        return false;
    }
}
