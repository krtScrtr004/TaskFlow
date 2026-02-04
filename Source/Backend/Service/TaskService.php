<?php

namespace App\Service;

use App\Container\ResourceContainer;
use App\Container\WorkerContainer;
use App\Core\Connection;
use App\Core\UUID;
use App\Entity\TaskResource;
use App\Entity\TaskWorker;
use App\Entity\ResourceType;
use App\Entity\Task;
use App\Enumeration\ResourceTypeMapping;
use App\Model\ResourceModel;
use App\Model\TaskModel;
use App\Model\TaskWorkerModel;
use InvalidArgumentException;
use PDO;
use Throwable;

class TaskService
{
    private PDO $connection;

    private TaskModel $taskModel;
    private TaskWorkerModel $taskWorkerModel;
    private ResourceModel $resourceModel;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        $this->connection = Connection::getInstance();

        $this->taskModel = new TaskModel();
        $this->taskWorkerModel = new TaskWorkerModel();
        $this->resourceModel = new ResourceModel();
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
    ]): Task
    {
        $executeTask = $execute['task'] ?? false;
        $executeWorker = $execute['worker'] ?? false;
        $executeResource = $execute['resource'] ?? false;

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            // Save new task entry
            if ($executeTask)
                $createdTask = $instance->taskModel->create($task);
            $taskId = $createdTask?->getID() ?? $task->getID();

            // Save task workers
            if ($executeWorker) {
                $taskWorkers = $task->getWorkers();
                if ($taskWorkers) {
                    $createdWorkers = $instance->taskWorkerModel->createMultiple($taskId, $taskWorkers);
                    // Create labor resources for each worker
                    $instance->createWorkerResources($taskId, $createdWorkers);
                }
            }

            // Save additional resources (non-labor: materials, equipment, etc.)
            if ($executeResource) {
                $taskResources = $task->getResources()->getResources();
                if ($taskResources && $taskResources->count() > 0)
                    $instance->resourceModel->createMultiple($taskId, $taskResources);
            }

            $instance->connection->commit();
            return $createdTask ?? $task;
        } catch (Throwable $e) {
            $instance->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Creates labor resources for each TaskWorker associated with a Task.
     * 
     * This private helper method generates TaskResource entries of type 'labor'
     * for each TaskWorker provided. It calculates the unit rate and estimated
     * hours based on the TaskWorker's properties and associates them with the
     * specified Task ID.
     * 
     * @param int $taskId The ID of the Task to which the resources will be linked.
     * @param WorkerContainer $workers A container of TaskWorker entities for which
     *                                  labor resources will be created.
     * 
     * @return void
     */
    private function createWorkerResources(int $taskId, WorkerContainer $workers): void
    {
        $taskWorkerResources = new ResourceContainer();
        foreach ($workers as $worker) {
            $taskWorkerResources->add(TaskResource::createPartial([
                'type'          => ResourceType::createPartial(['id' => ResourceTypeMapping::LABOR->value]),
                'quantity'      => 1,
                'unitRate'      => $worker->getUnitRate() !== DEFAULT_RATE_MIN
                    ? $worker->getUnitRate()
                    : $worker->getDefaultRate(),
                'estimatedUnit' => $worker->getEstimatedHour(),
                'taskWorkerId'  => $worker->getId()
            ]));
        }
        $this->resourceModel->createMultiple($taskId, $taskWorkerResources);
    }

    /**
     * Edits an existing Task along with its associated TaskWorkers and Resources.
     * 
     * This method updates the details of an existing Task entity in the database,
     * including its related TaskWorker entities and Resource entries. It ensures
     * that all updates are performed within a single transactional operation to
     * maintain data integrity.
     * 
     * @param array $rawTask An associative array containing the updated Task data,
     *                       including optional 'workers' and 'resources' sub-arrays.
     * 
     * @return bool True if the edit operation was successful, false otherwise.
     * 
     * @throws Throwable If any error occurs during the edit process, the transaction is rolled back.
     */
    public static function save(array $rawTask): bool
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            /**
             * Update task entry
             * 
             * Required:
             * - Task ID or Public ID
             */
            $instance->taskModel->save($rawTask);

            // Update task workers
            if (isset($rawTask['workers'])) {
                // Add new workers
                if (\count($rawTask['workers']['toAdd'] ?? []) > 0) {
                    $workersToAdd = new WorkerContainer();
                    foreach ($rawTask['workers']['toAdd'] as $workerData) {
                        if (isset($workerData['workerId'])) {
                            if ($workerData['workerId'] instanceof UUID)
                                $workerData['publicId'] = $workerData['workerId'];
                            elseif (\is_string($workerData['workerId']))
                                $workerData['publicId'] = UUID::fromString($workerData['workerId']);
                            elseif (\is_numeric($workerData['workerId']))
                                $workerData['id'] = (int)$workerData['workerId'];
                        }
                        $workersToAdd->add(TaskWorker::createPartial($workerData));
                    }
                    $createdWWorkers = $instance->taskWorkerModel->createMultiple($rawTask['id'] ?? $rawTask['publicId'], $workersToAdd);
                    // Create labor resources for each new worker
                    $instance->createWorkerResources($rawTask['id'] ?? $rawTask['publicId'], $createdWWorkers);
                }

                // Update existing workers and remove terminated ones
                foreach ($rawTask['workers'] as $category => $workers) {
                    if ($category === 'toAdd') continue;

                    foreach ($workers as $workerData) {
                        /**
                         * Required:
                         * - Task ID or Public ID
                         * - Worker ID or Public ID
                         */
                        $instance->taskWorkerModel->save($workerData);
                    }
                }
            }

            // Update resources
            if (isset($rawTask['resources'])) {
                /**
                 * Required:
                 * - Resource ID or Public ID
                 */
                foreach ($rawTask['resources'] as $resourceData) {
                    $instance->resourceModel->save($resourceData);
                }
            }

            $instance->connection->commit();
            return true;
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
     * @throws InvalidArgumentException If the provided task ID is invalid.
     */
    public static function get(
        int|UUID $taskId,
        array $options = [
            'workers'   => false,
            'resources' => false
        ]
    ): Task|null {
        if (\is_int($taskId) && $taskId <= 0)
            throw new InvalidArgumentException('Invalid task ID');

        $includeWorkers = $options['workers'] ?? false;
        $includeResources = $options['resources'] ?? false;

        $instance = new self();
        $task = $instance->taskModel->findById($taskId);
        if (!$task) return null;

        // Load related entities based on options
        if ($includeWorkers) {
            $workers = $instance->taskWorkerModel->findByTaskId($task->getId());
            $task->setWorkers($workers);
        }

        // Load resources if specified
        if ($includeResources) {
            $instance = new self();
            $resources = $instance->resourceModel->findByTaskId($task->getId());
            $task->setResources($resources);
        }

        return $task;
    }
}
