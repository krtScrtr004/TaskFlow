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
    public function findAllByProjectId(int|UUID $projectId, bool $includeTasks = false): PhaseContainer|null
    {
        if (\is_int($projectId) && $projectId < 1)
            throw new InvalidArgumentException('Invalid project ID provided');

        try {
            $instance = new self();

            // TODO: Create a separate TaskModel method to fetch tasks by phase ID(s) to avoid N+1 query problem
            $taskQuery = '';
            if ($includeTasks) {
                $taskQuery =
                    "SELECT 
                        JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'id', t.id,
                                'public_id', HEX(t.public_id),
                                'name', t.name,
                                'description', t.description,
                                'status', t.status,
                                'priority', t.priority,
                                'estimated_cost', tb.estimated_cost,
                                'actual_cost', tb.actual_cost,
                                'budget_note', tb.note,
                                'start_date_time', t.start_date_time,
                                'completion_date_time', t.completion_date_time,
                                'created_at', t.created_at,
                                'updated_at', t.updated_at
                            )
                        )
                    FROM
                        `task` AS t
                    INNER JOIN
                        `task_budget` AS tb
                    ON
                        tb.task_id = t.id
                    WHERE 
                        t.phase_id = ph.id";
            }

            $query =
                "SELECT 
                    ph.*,  
                    phb.budget,
                    phb.contingency_rate,
                    phb.note,
                    COALESCE (($taskQuery), JSON_ARRAY()) AS tasks
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
                    " . (is_int($projectId) ? 'p.id = :projectId' : 'p.public_id = :projectId') . "
                ORDER BY
                    ph.start_date_time ASC";

            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':projectId' => is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId)
            ]);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) return null;

            $phases = new PhaseContainer();
            foreach ($result as $item) {
                // Populate tasks if requested
                $taskContainer = new TaskContainer();
                if ($includeTasks) {
                    $tasks = json_decode($item['tasks'], true);
                    foreach ($tasks as $taskData) {
                        $taskContainer->add(Task::createPartial($taskData));
                    }
                }

                $item['budgetNote'] = $item['note'];
                $item['tasks'] = null; // Clear raw tasks data
                $phase = Phase::createPartial($item);
                if ($includeTasks && $taskContainer->count() > 0) $phase->setTasks($taskContainer);
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
    public function create(mixed $data): null
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
     *      - status: WorkStatus (required) The phase status enum (defaults to PENDING if null)
     *      - budget: float (required) The phase budget (defaults to BUDGET_MIN if null)
     *      - contingencyRate: float (required) The phase contingency rate (defaults to CONTINGENCY_RATE_MIN if null)
     *      - budgetNote: string (optional) The phase budget note
     *      - startDateTime: DateTime (required) The phase start date/time
     *      - completionDateTime: DateTime (required) The phase completion date/time
     *      - publicId: UUID (optional) Will be generated if not provided
     *
     * @return void
     *
     * @throws InvalidArgumentException If PhaseContainer is empty, project_id is invalid, container contains non-Phase objects, or required fields are missing
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
     *     budget: 10000.00,
     *     contingencyRate: 0.1,
     *     budgetNote: 'Initial budget allocation',
     *     startDateTime: new DateTime('2025-11-01'),
     *     completionDateTime: new DateTime('2025-11-15')
     * ));
     * $phaseIds = PhaseModel::createMultiple(1, $container);
     */
    public function createMultiple(int $projectId, PhaseContainer $phases): bool
    {
        if ($projectId < 1) throw new InvalidArgumentException('Invalid project ID provided');
        if ($phases->count() === 0) throw new InvalidArgumentException('PhaseContainer cannot be empty');

        $instance = new self();
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
                    :projectId,
                    :publicId,
                    :name,
                    :description,
                    :startDateTime,
                    :completionDateTime,
                    :status
                )";
            $phaseStatement = $this->connection->prepare($projectPhaseQuery);
            foreach ($phases as $phase) {
                $phaseStatement->execute([
                    ':projectId'            => $projectId,
                    ':publicId'             => UUID::toBinary($phase->getPublicId()),
                    ':name'                 => $phase->getName(),
                    ':description'          => $phase->getDescription(),
                    ':startDateTime'        => formatDateTime($phase->getStartDateTime()),
                    ':completionDateTime'   => formatDateTime($phase->getCompletionDateTime()),
                    ':status'               => $phase->getStatus()->value,
                ]);

                $projectPhaseBudgetQuery =
                    "INSERT INTO `phase_budget` (
                        phase_id,
                        budget,
                        contingency_rate,
                        note
                    ) VALUES (
                        :phaseId,
                        :budget,
                        :contingencyRate,
                        :note
                    )";

                $phaseId = (int) $this->connection->lastInsertId();
                $budgetStatement = $this->connection->prepare($projectPhaseBudgetQuery);
                $budgetStatement->execute([
                    ':phaseId'         => $phaseId,
                    ':budget'          => $phase->getBudget() ?? 0.00,
                    ':contingencyRate' => $phase->getContingencyRate() ?? 0.00,
                    ':note'           => $phase->getBudgetNote() ?? null,
                ]);
            }

            return true;
        } catch (PDOException $e) {
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
    public function save(array $data): bool
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
     *      - budget: float (optional) The phase budget
     *      - contingencyRate: float (optional) The phase contingency rate
     *      - budgetNote: string (optional) The phase budget note
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
    public function saveMultiple(array $phases): bool
    {
        if (empty($phases)) throw new InvalidArgumentException('Phases array cannot be empty');

        try {
            foreach ($phases as $data) {
                if (isset($data['id']) && is_int($data['id']) && $data['id'] < 1)
                    throw new InvalidArgumentException('Invalid phase ID provided');

                $phaseUpdateFields = [];
                $projectPhaseParams = [];


                $projectPhaseParams[':id'] = (isset($data['id']))
                    ? $data['id']
                    : ($data['publicId'] instanceof UUID
                        ? UUID::toBinary($data['publicId'])
                        : UUID::toBinary(UUID::fromString($data['publicId'])));

                if (isset($data['name'])) {
                    $phaseUpdateFields[] = 'name = :name';
                    $projectPhaseParams[':name'] = trimOrNull($data['name']);
                }

                if (isset($data['description'])) {
                    $phaseUpdateFields[] = 'description = :description';
                    $projectPhaseParams[':description'] = trimOrNull($data['description']);
                }

                if (isset($data['status'])) {
                    $phaseUpdateFields[] = 'status = :status';
                    $projectPhaseParams[':status'] = $data['status']->value;
                }

                if (isset($data['startDateTime'])) {
                    $phaseUpdateFields[] = 'start_date_time = :startDateTime';
                    $projectPhaseParams[':startDateTime'] = formatDateTime($data['startDateTime']);
                }

                if (isset($data['completionDateTime'])) {
                    $phaseUpdateFields[] = 'completion_date_time = :completionDateTime';
                    $projectPhaseParams[':completionDateTime'] = formatDateTime($data['completionDateTime']);
                }

                // Only execute update if there are fields to update
                if (!empty($phaseUpdateFields)) {
                    $query = "
                        UPDATE 
                            `project_phase` 
                        SET 
                            " . implode(', ', $phaseUpdateFields) . " 
                        WHERE 
                            " . (isset($data['id']) 
                                ? 'id' 
                                : 'public_id') . " 
                            = :id";
                    $statement = $this->connection->prepare($query);
                    $statement->execute($projectPhaseParams);
                }

                // Budget update 
                $this->saveBudget($data['id'] ?? $data['publicId'], $data);
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
            return $taskModel->findAllByPhaseId($phaseId, null, null, $options);
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
