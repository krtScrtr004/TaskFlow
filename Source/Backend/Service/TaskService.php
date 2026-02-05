<?php

namespace App\Service;

use App\Container\ResourceContainer;
use App\Container\TaskContainer;
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
    public function __construct()
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
    public function create(
        Task|TaskContainer $task,
        array $execute = [
            'task'      => true,
            'worker'    => true,
            'resource'  => true
        ]
    ): Task|TaskContainer {
        $isBatch = $task instanceof TaskContainer;
        $tasks = $isBatch ? $task : new TaskContainer([$task]);

        $executeTask = $execute['task'] ?? false;
        $executeWorker = $execute['worker'] ?? false;
        $executeResource = $execute['resource'] ?? false;

        try {
            $this->connection->beginTransaction();

            $createdTasks = new TaskContainer();
            foreach ($tasks as $item) {
                // Save new task entry
                if ($executeTask)
                    $createdTask = $this->taskModel->create($item);
                $taskId = $createdTask?->getID() ?? $item->getID();

                // Save task workers
                if ($executeWorker) {
                    $taskWorkers = $item->getWorkers();
                    if ($taskWorkers) {
                        $createdWorkers = $this->taskWorkerModel->create($taskId, $taskWorkers);
                        // Create labor resources for each worker
                        $this->createWorkerResources($taskId, $createdWorkers);
                    }
                }

                // Save additional resources (non-labor: materials, equipment, etc.)
                if ($executeResource) {
                    $taskResources = $item->getResources()->getResources();
                    if ($taskResources && $taskResources->count() > 0)
                        $this->resourceModel->create($taskId, $taskResources);
                }
                $createdTasks->add($createdTask ?? $item);
            }

            $this->connection->commit();
            return $isBatch ? $createdTasks : $createdTasks->first();
        } catch (Throwable $e) {
            $this->connection->rollBack();
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
        $this->resourceModel->create($taskId, $taskWorkerResources);
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
    public function save(array $rawTask): bool
    {
        $isBatch = array_keys($rawTask) === range(0, count($rawTask) - 1);
        $rawTasks = $isBatch ? $rawTask : [$rawTask];

        try {
            $this->connection->beginTransaction();

            foreach ($rawTasks as $item) {
                /**
                 * Update task entry
                 * 
                 * Required:
                 * - Task ID or Public ID
                 */
                $this->taskModel->save($item);

                // Update task workers
                if (isset($item['workers'])) {
                    // Add new workers
                    if (\count($item['workers']['toAdd'] ?? []) > 0) {
                        $workersToAdd = new WorkerContainer();
                        foreach ($item['workers']['toAdd'] as $workerData) {
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
                        $createdWWorkers = $this->taskWorkerModel->create($item['id'] ?? $item['publicId'], $workersToAdd);
                        // Create labor resources for each new worker
                        $this->createWorkerResources($item['id'] ?? $item['publicId'], $createdWWorkers);
                    }

                    // Update existing workers and remove terminated ones
                    foreach ($item['workers'] as $category => $workers) {
                        if ($category === 'toAdd') continue;

                        foreach ($workers as $workerData) {
                            /**
                             * Required:
                             * - Task ID or Public ID
                             * - Worker ID or Public ID
                             */
                            $this->taskWorkerModel->save($workerData);
                        }
                    }
                }

                // Update resources
                if (isset($item['resources'])) {
                    /**
                     * Required:
                     * - Resource ID or Public ID
                     */
                    foreach ($item['resources'] as $resourceData) {
                        $this->resourceModel->save($resourceData);
                    }
                }
            }

            $this->connection->commit();
            return true;
        } catch (Throwable $e) {
            $this->connection->rollBack();
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
    public function get(
        int|UUID|array $taskId,
        array $options = [
            'workers'   => false,
            'resources' => false
        ]
    ): Task|TaskContainer|null {
        $isBatch = \is_array($taskId);
        /**  @var array<int|UUID> $taskId  */
        $taskIds = $isBatch ? array_values($taskId) : [$taskId];

        $tasks = new TaskContainer();
        foreach ($taskIds as $item) {
            if (!\is_int($item) && !($item instanceof UUID))
                throw new InvalidArgumentException('Task ID must be an integer or UUID');

            if (\is_int($item) && $item <= 0)
                throw new InvalidArgumentException('Invalid task ID');

            $includeWorkers = $options['workers'] ?? false;
            $includeResources = $options['resources'] ?? false;

                $task = $this->taskModel->findById($item);
            if (!$task) return null;

            // Load related entities based on options
            if ($includeWorkers) {
                $workers = $this->taskWorkerModel->findByTaskId($task->getId());
                $task->setWorkers($workers);
            }

            // Load resources if specified
            if ($includeResources) {
                        $resources = $this->resourceModel->findByTaskId($task->getId());
                $task->setResources($resources);
            }

            $tasks->add($task);
        }

        return $isBatch ? $tasks : $tasks->first();
    }
}
