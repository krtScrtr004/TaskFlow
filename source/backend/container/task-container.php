<?php

namespace App\Container;

use App\Abstract\Container;
use App\Entity\Task;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use InvalidArgumentException;

class TaskContainer extends Container
{
    private array $taskCountByStatus = [
        WorkStatus::PENDING->value => 0,
        WorkStatus::ON_GOING->value => 0,
        WorkStatus::COMPLETED->value => 0,
        WorkStatus::DELAYED->value => 0,
        WorkStatus::CANCELLED->value => 0,
    ];
    private array $taskCountByPriority = [
        TaskPriority::LOW->value => 0,
        TaskPriority::MEDIUM->value => 0,
        TaskPriority::HIGH->value => 0,
    ];

    /**
     * Initializes the container with an array of Task instances.
     *
     * This constructor accepts an array of tasks and adds each task to the container
     * by calling add(). Validation performed:
     * - Ensures each element in the array is an instance of Task
     * - Adds each valid Task to the container
     * - Throws an exception immediately when a non-Task element is encountered
     *
     * @param Task[] $tasks Indexed array of Task objects to populate the container
     *
     * @throws InvalidArgumentException If any element of $tasks is not an instance of Task
     */
    public function __construct(array $tasks = [])
    {
        foreach ($tasks as $task) {
            if (!($task instanceof Task)) {
                throw new InvalidArgumentException("All elements of tasks array must be instances of Task.");
            }

            $this->add($task);
        }
    }

    /**
     * Adds a Task instance to the container.
     *
     * Validates that the provided value is a Task instance and throws an
     * InvalidArgumentException otherwise. On success, it updates internal
     * bookkeeping by calling increaseTaskCount($task) and stores the Task
     * in the container's items array using the Task's ID as the key
     * (retrieved via $task->getId()).
     *
     * Note: If a Task with the same ID already exists in the container, it
     * will be overwritten.
     *
     * @param Task $task The Task instance to add to the container
     * @throws InvalidArgumentException If the provided argument is not a Task
     * @return void
     */
    public function add($task): void
    {
        if (!$task instanceof Task) {
            throw new InvalidArgumentException("Only Task instances can be added to TaskContainer.");
        }
        $this->increaseTaskCount($task);
        $this->items[$task->getId()] = $task;
    }

    /**
     * Removes a Task instance from the container.
     *
     * This method ensures the provided item is a Task instance and performs necessary
     * housekeeping before removing it from the internal storage:
     * - Validates that $item is an instance of Task and throws InvalidArgumentException otherwise
     * - Calls decreaseTaskCount($item) to update internal counters/state related to task tracking
     * - Removes the task from $this->items using $item->getId() as the key
     *
     * @param Task $item Task instance to remove from the container
     *
     * @throws InvalidArgumentException If $item is not an instance of Task
     *
     * @return void
     */
    public function remove($item): void
    {
        if (!$item instanceof Task) {
            throw new InvalidArgumentException('Only Task instances can be removed from TaskContainer.');
        }

        $this->decreaseTaskCount($item);
        unset($this->items[$item->getId()]);
    }

    /**
     * Determines whether the container contains the given Task.
     *
     * This method enforces that the provided item is a Task instance and then
     * checks for existence by looking up the Task's identifier in the
     * container's internal storage using isset (which provides an efficient
     * membership test).
     *
     * - Validates that $item is an instance of Task
     * - Uses $item->getId() to identify the Task in the container
     * - Uses isset on the internal items array for the membership check
     *
     * @param mixed $item The value to check for membership (must be a Task)
     *
     * @return bool True if a Task with the same id exists in the container, false otherwise
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Task
     */
    public function contains($item): bool
    {
        if (!$item instanceof Task) {
            throw new InvalidArgumentException('Only Task instances can be checked in TaskContainer.');
        }
        return isset($this->items[$item->getId()]);
    }

    /**
     * Increments internal task counters based on the provided Task instance.
     *
     * This method performs the following actions:
     * - Retrieves the status scalar from the Task's Status enum via $task->getStatus()->value and, if that key exists in $this->taskCountByStatus, increments its count.
     * - Retrieves the priority scalar from the Task's Priority enum via $task->getPriority()->value and, if that key exists in $this->taskCountByPriority, increments its count.
     *
     * Important details:
     * - Keys are only incremented when they already exist in the respective arrays; no new keys are created.
     * - Updates are performed in-place on $this->taskCountByStatus and $this->taskCountByPriority.
     *
     * @param Task $task Task instance providing status and priority enums
     * @return void
     */
    private function increaseTaskCount(Task $task): void
    {
        $status = $task->getStatus()->value;
        if (array_key_exists($status, $this->taskCountByStatus)) {
            $this->taskCountByStatus[$status]++;
        }

        $priority = $task->getPriority()->value;
        if (array_key_exists($priority, $this->taskCountByPriority)) {
            $this->taskCountByPriority[$priority]++;
        }
    }

    /**
     * Decrements internal counters for the given Task's status and priority.
     *
     * This method obtains the Task's status and priority via $task->getStatus()->value
     * and $task->getPriority()->value and reduces the corresponding counts stored
     * in $this->taskCountByStatus and $this->taskCountByPriority.
     *
     * Behavior details:
     * - If a status (or priority) key exists in the respective array and its value is
     *   greater than zero, it is decremented by one.
     * - If the key does not exist or the current count is zero or less, no change is made.
     * - This ensures counters never become negative.
     *
     * Expected internal array shape:
     * - $this->taskCountByStatus: array<string|int, int> mapping status values to counts
     * - $this->taskCountByPriority: array<string|int, int> mapping priority values to counts
     *
     * Side effects:
     * - Mutates $this->taskCountByStatus and/or $this->taskCountByPriority when applicable.
     *
     * @param Task $task Task whose status and priority counts should be decreased
     *
     * @return void
     */
    private function decreaseTaskCount(Task $task): void
    {
        $status = $task->getStatus()->value;
        if (array_key_exists($status, $this->taskCountByStatus) && $this->taskCountByStatus[$status] > 0) {
            $this->taskCountByStatus[$status]--;
        }

        $priority = $task->getPriority()->value;
        if (array_key_exists($priority, $this->taskCountByPriority) && $this->taskCountByPriority[$priority] > 0) {
            $this->taskCountByPriority[$priority]--;
        }
    }

    /**
     * Returns the number of tasks registered for the given work status.
     *
     * This method:
     * - Accepts a WorkStatus enum instance and uses its scalar value as the lookup key.
     * - Looks up the count in the internal $taskCountByStatus associative array.
     * - Returns 0 when no entry exists for the provided status.
     *
     * @param WorkStatus $status WorkStatus enum instance whose scalar value is used as the array key.
     *
     * @return int Number of tasks for the specified status, or 0 if none are recorded.
     */
    public function getTaskCountByStatus(WorkStatus $status): int
    {
        $statusValue = $status->value;
        return $this->taskCountByStatus[$statusValue] ?? 0;
    }

    /**
     * Returns the container's counts of tasks grouped by their status.
     *
     * This method provides an associative array where each key is a task status
     * identifier (string) and each value is the number of tasks currently in that
     * status (int). The returned array is a snapshot of the container's stored
     * counts and can be safely read or iterated by callers.
     *
     * @return array<string,int> Associative array mapping task status to task count.
     *      Example structure:
     *      - 'pending' => 5
     *      - 'in_progress' => 3
     *      - 'completed' => 12
     */
    public function getAllTaskCountByStatus(): array
    {
        return $this->taskCountByStatus;
    }

    /**
     * Returns the number of tasks that have the specified priority.
     *
     * This method retrieves the integer value of the provided TaskPriority enum
     * and uses it as a key into the internal task count map:
     * - Uses $priority->value as the lookup key in $this->taskCountByPriority
     * - If no entry exists for the given priority value, returns 0
     *
     * @param TaskPriority $priority The priority enum to query.
     *
     * @return int The count of tasks for the given priority (0 when none exist).
     */
    public function getTaskCountByPriority(TaskPriority $priority): int
    {
        $priorityValue = $priority->value;
        return $this->taskCountByPriority[$priorityValue] ?? 0;
    }

    /**
     * Returns counts of all tasks grouped by priority.
     *
     * This method provides an associative array representing a snapshot of task counts
     * organized by priority. It is intended to give callers an easy way to inspect
     * how many tasks exist for each priority level:
     * - Keys are priority identifiers (e.g. string names like "low", "medium", "high"
     *   or numeric priority IDs depending on the application's convention)
     * - Values are integers representing the number of tasks for that priority
     *
     * The returned array may include priorities with a count of 0. Consumers should
     * treat the array as read-only and not rely on its contents being updated after
     * retrieval (call this method again to obtain an updated snapshot).
     *
     * @return array<string,int> Associative array mapping priority identifiers to task counts
     */
    public function getAllTaskCountByPriority(): array
    {
        return $this->taskCountByPriority;
    }

    /**
     * Convert the container's tasks to an array representation.
     *
     * This method iterates over the container's stored items and converts each item
     * to its array form by invoking the item's toArray() method:
     * - Calls toArray() on each task item
     * - Preserves the original order of items
     * - Expects each item to provide a toArray() method (e.g. implement Task or TaskInterface)
     * - Any exceptions thrown by an individual task's toArray() will propagate to the caller
     *
     * @return array<int, array<string, mixed>> Array of tasks where each task is represented as an associative array
     */
    public function toArray(): array
    {
        $tasksArray = [];
        foreach ($this->items as $task) {
            $tasksArray[] = $task->toArray();
        }
        return $tasksArray;
    }

    /**
     * Creates a TaskContainer instance from an array of task data.
     *
     * This method transforms an array of task data into a TaskContainer object by:
     * - Converting each element of the array to a Task object using Task::fromArray()
     * - Initializing a new TaskContainer with the resulting array of Task objects
     *
     * @param array $data Array of task data arrays, where each element is an instance of Task 
     *              or an array containing the necessary data to create a Task instance
     * 
     * @return TaskContainer New TaskContainer instance containing all the Task objects created from the provided data
     */
    public static function fromArray(array $data): TaskContainer
    {
        $tasks = new self();
        foreach ($data as $taskData) {
            if ($taskData instanceof Task) {
                $tasks->add($taskData);
            } else {
                $tasks->add(Task::fromArray($taskData));
            }
        }
        return $tasks;
    }
}
