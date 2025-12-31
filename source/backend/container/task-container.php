<?php

namespace App\Container;

use App\Abstract\Container;
use App\Entity\Task;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use InvalidArgumentException;

class TaskContainer extends Container
{
    private array $pending = [];
    private array $ongoing = [];
    private array $completed = [];
    private array $delayed = [];
    private array $cancelled = [];

    private array $low = [];
    private array $medium = [];
    private array $high = [];

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
     * Adds a Task instance to the TaskContainer.
     *
     * This method ensures that only valid Task instances are added to the container. It categorizes
     * the task based on its status and priority, storing it in the appropriate internal arrays for
     * efficient management and retrieval.
     *
     * Behavior and side effects:
     * - Validates that the provided argument is an instance of Task and throws an exception if not.
     * - Retrieves the task's ID, status, and priority using the respective getter methods.
     * - Stores the task in status-specific arrays: $this->pending, $this->ongoing, $this->completed,
     *   $this->delayed, or $this->cancelled, based on the task's status.
     * - Stores the task in priority-specific arrays: $this->low, $this->medium, or $this->high,
     *   based on the task's priority.
     * - Adds the task to the main $this->items array indexed by its ID.
     * - If a task with the same ID already exists, it will be overwritten in all relevant arrays.
     *
     * @param mixed $item Task instance to add to the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Task
     *
     * @return void
     */
    public function add($item): void
    {
        if (!$item instanceof Task) {
            throw new InvalidArgumentException("Only Task instances can be added to TaskContainer.");
        }

        $id = $item->getId();
        $status = $item->getStatus();
        $priority = $item->getPriority();
        switch ($status) {
            case WorkStatus::PENDING:
                $this->pending[$id] = $item;
                break;
            case WorkStatus::ONGOING:
                $this->ongoing[$id] = $item;
                break;
            case WorkStatus::COMPLETED:
                $this->completed[$id] = $item;
                break;
            case WorkStatus::DELAYED:
                $this->delayed[$id] = $item;
                break;
            case WorkStatus::CANCELLED:
                $this->cancelled[$id] = $item;
                break;
        }

        switch ($priority) {
            case TaskPriority::LOW:
                $this->low[$id] = $item;
                break;
            case TaskPriority::MEDIUM:
                $this->medium[$id] = $item;
                break;
            case TaskPriority::HIGH:
                $this->high[$id] = $item;
                break;
        }

        $this->items[$id] = $item;
    }

    /**
     * Removes a Task instance from the TaskContainer.
     *
     * This method ensures that only valid Task instances are removed from the container. It
     * identifies the task by its ID and removes it from all relevant internal arrays based
     * on its status and priority.
     *
     * Behavior and side effects:
     * - Validates that the provided argument is an instance of Task and throws an exception if not.
     * - Retrieves the task's ID, status, and priority using the respective getter methods.
     * - Removes the task from status-specific arrays: $this->pending, $this->ongoing, $this->completed,
     *   $this->delayed, or $this->cancelled, based on the task's status.
     * - Removes the task from priority-specific arrays: $this->low, $this->medium, or $this->high,
     *   based on the task's priority.
     * - Removes the task from the main $this->items array indexed by its ID.
     * - If the task does not exist in any of the arrays, no action is taken.
     *
     * @param mixed $item Task instance to remove from the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Task
     *
     * @return void
     */
    public function remove($item): void
    {
        if (!$item instanceof Task) {
            throw new InvalidArgumentException('Only Task instances can be removed from TaskContainer.');
        }

        $id = $item->getId();
        $status = $item->getStatus();
        $priority = $item->getPriority();

        switch ($status) {
            case WorkStatus::PENDING:
                unset($this->pending[$id]);
                break;
            case WorkStatus::ONGOING:
                unset($this->ongoing[$id]);
                break;
            case WorkStatus::COMPLETED:
                unset($this->completed[$id]);
                break;
            case WorkStatus::DELAYED:
                unset($this->delayed[$id]);
                break;
            case WorkStatus::CANCELLED:
                unset($this->cancelled[$id]);
                break;
        }

        switch ($priority) {
            case TaskPriority::LOW:
                unset($this->low[$id]);
                break;
            case TaskPriority::MEDIUM:
                unset($this->medium[$id]);
                break;
            case TaskPriority::HIGH:
                unset($this->high[$id]);
                break;
        }

        unset($this->items[$id]);
    }

    /**
     * Checks if a Task instance exists in the TaskContainer.
     *
     * This method verifies the presence of a Task in the container by checking its ID,
     * status, and priority across all relevant internal arrays.
     *
     * Behavior and side effects:
     * - Validates that the provided argument is an instance of Task and throws an exception if not.
     * - Retrieves the task's ID, status, and priority using the respective getter methods.
     * - Checks for the task's existence in the main $this->items array indexed by its ID.
     * - Checks for the task's existence in status-specific arrays: $this->pending, $this->ongoing,
     *   $this->completed, $this->delayed, or $this->cancelled, based on the task's status.
     * - Checks for the task's existence in priority-specific arrays: $this->low, $this->medium,
     *   or $this->high, based on the task's priority.
     * - Returns true only if the task is found in all relevant arrays; otherwise returns false.
     *
     * @param mixed $item Task instance to check for existence in the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Task
     *
     * @return bool True if the Task exists in the container; false otherwise
     */
    public function contains($item): bool
    {
        if (!$item instanceof Task) {
            throw new InvalidArgumentException('Only Task instances can be checked in TaskContainer.');
        }
        $id = $item->getId();
        $status = $item->getStatus();
        $priority = $item->getPriority();

        $isPresentAll = isset($this->items[$id]);

        $isPresentStatus = false;
        switch ($status) {
            case WorkStatus::PENDING:
                $isPresentStatus = isset($this->pending[$id]);
                break;
            case WorkStatus::ONGOING:
                $isPresentStatus = isset($this->ongoing[$id]);
                break;
            case WorkStatus::COMPLETED:
                $isPresentStatus = isset($this->completed[$id]);
                break;
            case WorkStatus::DELAYED:
                $isPresentStatus = isset($this->delayed[$id]);
                break;
            case WorkStatus::CANCELLED:
                $isPresentStatus = isset($this->cancelled[$id]);
                break;
        }

        $isPresentPriority = false;
        switch ($priority) {
            case TaskPriority::LOW:
                $isPresentPriority = isset($this->low[$id]);
                break;
            case TaskPriority::MEDIUM:
                $isPresentPriority = isset($this->medium[$id]);
                break;
            case TaskPriority::HIGH:
                $isPresentPriority = isset($this->high[$id]);
                break;
        }

        return $isPresentAll && $isPresentStatus && $isPresentPriority;
    }

    /**
     * Gets the count of tasks by their work status.
     *
     * This method retrieves the number of tasks in the container that match the specified
     * work status. It uses the internal arrays that categorize tasks by their status to
     * efficiently return the count.
     *
     * @param WorkStatus $status The work status to count tasks for
     *
     * @return int The count of tasks with the specified work status
     */
    public function countByStatus(WorkStatus $status): int
    {
        switch ($status) {
            case WorkStatus::PENDING:
                return count($this->pending) ?? 0;
            case WorkStatus::ONGOING:
                return count($this->ongoing) ?? 0;
            case WorkStatus::COMPLETED:
                return count($this->completed) ?? 0;
            case WorkStatus::DELAYED:
                return count($this->delayed) ?? 0;
            case WorkStatus::CANCELLED:
                return count($this->cancelled) ?? 0;
            default:
                return 0;
        }
    }

    /**
     * Returns counts of all tasks grouped by their work status.
     *
     * This method provides an associative array representing a snapshot of task counts
     * organized by work status. It is intended to give callers an easy way to inspect
     * how many tasks exist for each status:
     * - Keys are status identifiers (e.g. string names like "pending", "ongoing", etc.
     *   or numeric status IDs depending on the application's convention)
     * - Values are integers representing the number of tasks for that status
     *
     * The returned array may include statuses with a count of 0. Consumers should
     * treat the array as read-only and not rely on its contents being updated after
     * retrieval (call this method again to obtain an updated snapshot).
     *
     * @return array<string,int> Associative array mapping status identifiers to task counts
     */
    public function countAllByStatus(): array
    {
        return [
            WorkStatus::PENDING->value      => count($this->pending),
            WorkStatus::ONGOING->value     => count($this->ongoing),
            WorkStatus::COMPLETED->value    => count($this->completed),
            WorkStatus::DELAYED->value      => count($this->delayed),
            WorkStatus::CANCELLED->value    => count($this->cancelled),
        ];
    }

    /**
     * Gets the count of tasks by their priority.
     *
     * This method retrieves the number of tasks in the container that match the specified
     * priority. It uses the internal arrays that categorize tasks by their priority to
     * efficiently return the count.
     *
     * @param TaskPriority $priority The priority to count tasks for
     *
     * @return int The count of tasks with the specified priority
     */
    public function countByPriority(TaskPriority $priority): int
    {
        switch ($priority) {
            case TaskPriority::LOW:
                return count($this->low) ?? 0;
            case TaskPriority::MEDIUM:
                return count($this->medium) ?? 0;
            case TaskPriority::HIGH:
                return count($this->high) ?? 0;
            default:
                return 0;
        }
    }

    /**
     * Returns counts of all tasks grouped by their priority.
     *
     * This method provides an associative array representing a snapshot of task counts
     * organized by priority. It is intended to give callers an easy way to inspect
     * how many tasks exist for each priority:
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
    public function countAllByPriority(): array
    {
        return [
            TaskPriority::LOW->value      => count($this->low),
            TaskPriority::MEDIUM->value   => count($this->medium),
            TaskPriority::HIGH->value     => count($this->high),
        ];
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
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     * @return array<int, array<string, mixed>> Array of tasks where each task is represented as an associative array
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $tasksArray = [];
        foreach ($this->items as $task) {
            $tasksArray[] = $task->toArray($useSnakeCase);
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
