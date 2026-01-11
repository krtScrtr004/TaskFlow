<?php

namespace App\Service;

use App\Container\ResourceContainer;
use App\Core\Connection;
use App\Dependent\TaskResource;
use App\Entity\ResourceType;
use App\Entity\Task;
use App\Enumeration\ResourceTypeMapping;
use App\Model\ResourceModel;
use App\Model\TaskModel;
use App\Model\TaskWorkerModel;
use PDO;
use Throwable;

class TaskService 
{
    private PDO $connection;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    /**
     * Create a new Task with associated workers and resources within a single database transaction.
     *
     * This static factory method persists a Task and its related entities atomically:
     * - Begins a database transaction.
     * - Persists the Task via TaskModel::create and obtains the created task identifier.
     * - Persists any provided task workers via TaskWorkerModel::createMultiple.
     * - For each created worker, auto-creates a labor resource (quantity = 1) using the worker's
     *   default rate and estimated hours, associates it with the created task worker, and persists
     *   those resources via ResourceModel::createMultiple.
     * - Persists any additional non-labor resources supplied by $task->getResources()->getResources().
     * - Commits the transaction on success; rolls back and rethrows on failure.
     *
     * Behavior and side effects:
     * - Validates input by type-hinting Task; callers must supply a Task instance.
     * - Starts a DB transaction via $instance->connection->beginTransaction().
     * - Creates a Task record and returns the created Task instance (including its persisted ID).
     * - Creates worker records when $task->getWorkers() is non-empty.
     * - Automatically creates labor-type resources for each created worker with:
     *   - type set to the LABOR resource type,
     *   - quantity = 1,
     *   - unitRate set from $worker->getDefaultRate(),
     *   - estimatedUnit set from $worker->getEstimatedHours(),
     *   - taskWorkerId set to the created worker's ID.
     * - Persists additional resources from $task->getResources()->getResources() when present.
     * - Ensures atomicity: on any Throwable during persistence, the transaction is rolled back and
     *   the original exception is rethrown.
     * - Does not perform external side effects beyond the database changes described above.
     *
     * @param Task $task Task domain object to persist (including its requested workers and resources)
     *
     * @throws Throwable If any persistence operation fails; the transaction will be rolled back and
     *                   the original exception/error is propagated.
     *
     * @return Task The persisted Task instance (as returned by TaskModel::create), including its ID.
     */
    public static function create(Task $task): Task
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            // Save new task entry
            $createdTask = TaskModel::create($task);
            $createdTaskId = $createdTask->getID();

            // Save task workers
            $taskWorkers = $task->getWorkers();
            if ($taskWorkers) {
                $createdWorkers = TaskWorkerModel::createMultiple($createdTaskId, $taskWorkers);

                // Auto-create labor resources for each worker
                $taskWorkerResources = new ResourceContainer();
                foreach ($createdWorkers as $worker) {
                    $taskWorkerResources->add(TaskResource::createPartial([
                        'type'          => ResourceType::createPartial(['id' => ResourceTypeMapping::LABOR]),
                        'quantity'      => 1,
                        'unitRate'      => $worker->getDefaultRate(),
                        'estimatedUnit' => $worker->getEstimatedHours(),
                        'taskWorkerId'  => $worker->getId()
                    ]));
                }
                ResourceModel::createMultiple($createdTaskId, $taskWorkerResources);
            }

            // Save additional resources (non-labor: materials, equipment, etc.)
            $taskResources = $task->getResources();
            if ($taskResources && $taskResources->count() > 0)
                ResourceModel::createMultiple($createdTaskId, $taskResources);

            $instance->connection->commit();
            return $createdTask;
        } catch (Throwable $e) {
            $instance->connection->rollBack();
            throw $e;
        }
    }
}

/**
 * TASK {
 *     