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
     * Creates a new Task along with its associated TaskWorkers and TaskResources.
     * 
     * This method handles the creation of a Task entity, its related TaskWorker entities,
     * and their associated TaskResource entries in a single transactional operation.
     * It supports optional execution flags to control which components are created.
     * 
     * Behavior and side effects:
     * - Begins a database transaction to ensure atomicity of the operation.
     * - Creates the Task entry if $execute['task'] is true.
     * - Creates associated TaskWorker entries if $execute['worker'] is true.
     * - For each created TaskWorker, automatically creates a labor TaskResource entry.
     * - Creates additional TaskResource entries if $execute['resource'] is true.
     * - Commits the transaction upon successful completion of all operations.
     * - Rolls back the transaction if any error occurs during the process.
     * 
     * @param Task $task The Task entity to be created, including its workers and resources.
     * @param array $execute Optional associative array to control execution of components:
     *                       'task' => bool (default true),
     *                       'worker' => bool (default true),
     *                       'resource' => bool (default true).
     * 
     * @return Task The created Task entity with updated identifiers.
     * 
     * @throws Throwable If any error occurs during the creation process, the transaction is rolled back.
     */
    public static function create(Task $task, array $execute = [
        'task'      => true,
        'worker'    => true,
        'resource'  => true
    ]): Task {
        $executeTask = $execute['task'] ?? false;
        $executeWorker = $execute['worker'] ?? false;
        $executeResource = $execute['resource'] ?? false;

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            // Save new task entry
            if ($executeTask)
                $createdTask = TaskModel::create($task);
            $taskId = $createdTask?->getID() ?? $task->getID();

            // Save task workers
            if ($executeWorker) {
                $taskWorkers = $task->getWorkers();
                if ($taskWorkers) {
                    $createdWorkers = TaskWorkerModel::createMultiple($taskId, $taskWorkers);

                    // Auto-create labor resources for each worker
                    $taskWorkerResources = new ResourceContainer();
                    foreach ($createdWorkers as $worker) {
                        $taskWorkerResources->add(TaskResource::createPartial([
                            'type'          => ResourceType::createPartial(['id' => ResourceTypeMapping::LABOR->value]),
                            'quantity'      => 1,
                            'unitRate'      => $worker->getDefaultRate(),
                            'estimatedUnit' => $worker->getEstimatedHours(),
                            'taskWorkerId'  => $worker->getId()
                        ]));
                    }
                    ResourceModel::createMultiple($taskId, $taskWorkerResources);
                }
            }

            // Save additional resources (non-labor: materials, equipment, etc.)
            if ($executeResource) {
                $taskResources = $task->getResources()->getResources();
                if ($taskResources && $taskResources->count() > 0)
                    ResourceModel::createMultiple($taskId, $taskResources);
            }

            $instance->connection->commit();
            return $createdTask ?? $task;
        } catch (Throwable $e) {
            $instance->connection->rollBack();
            throw $e;
        }
    }
}