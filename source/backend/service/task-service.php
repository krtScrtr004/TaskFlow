<?php

namespace App\Service;

use App\Container\ResourceContainer;
use App\Core\Connection;
use App\Core\UUID;
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
                            'unitRate'      => $worker->getUnitRate() !== DEFAULT_RATE_MIN 
                                ? $worker->getUnitRate()
                                : $worker->getDefaultRate(),
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

    /**
     * Retrieves a Task by its ID, with optional inclusion of related entities.
     * 
     * This method fetches a Task entity from the database based on the provided task ID.
     * It supports optional parameters to include related entities such as TaskWorkers
     * and TaskResources in the returned Task object.
     * 
     * @param int|UUID $taskId The ID or UUID of the Task to retrieve.
     * @param array $options Optional associative array to specify related entities to include:
     *                       'workers' => bool (default false),
     *                       'resources' => bool (default false).
     * 
     * @return Task|null The retrieved Task entity with optional related entities, or null if not found.
     * 
     * @throws \InvalidArgumentException If the provided task ID is invalid.
     */
    public static function get(
        int|UUID $taskId, 
        array $options = [
            'workers'   => false,
            'resources' => false
        ]
    ): ?Task {
        if (is_int($taskId) && $taskId <= 0) 
            throw new \InvalidArgumentException('Invalid task ID.');

        $includeWorkers = $options['workers'] ?? false;
        $includeResources = $options['resources'] ?? false;

        $task = TaskModel::findById($taskId);

        // Load related entities based on options
        if ($task && $includeWorkers) {
            $workers = TaskWorkerModel::findByTaskId($task->getId());
            $task->setWorkers($workers);
        }
        
        // Load resources if specified
        if ($task && $includeResources) {
            $resources = ResourceModel::findByTaskId($task->getId());
            $task->setResources($resources);
        }

            return $task;
    }
}