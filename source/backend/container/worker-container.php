<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Worker;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use InvalidArgumentException;
use Traversable;
use ArrayIterator;

class WorkerContainer extends Container
{
    private array $unassigned = [];
    private array $assigned = [];
    private array $terminated = [];

    private float $totalDefaultRate = 0.0;

    /**
     * Constructs the container and populates it with Worker instances.
     *
     * This constructor accepts an array of workers and adds each element to the container
     * via the add() method. It validates that every element is an instance of Worker
     * and will throw an InvalidArgumentException when an invalid element is encountered.
     * The total default rate is initialized to DEFAULT_RATE_MIN.
     * 
     * @param array|Worker[] $workers Array of Worker instances to register in the container.
     *      - Each element MUST be an instance of Worker.
     *
     * @throws InvalidArgumentException If any element of $workers is not an instance of Worker.
     */
    public function __construct(array $workers = [])
    {
        $this->totalDefaultRate = DEFAULT_RATE_MIN;
        foreach ($workers as $worker) {
            if (!($worker instanceof Worker)) {
                throw new InvalidArgumentException("All elements of workers array must be instances of Worker.");
            }
            $this->add($worker);
        }
    }

    /**
     * Adds a Worker instance to the container.
     *
     * This method enforces that the provided argument is a Worker instance with the 'worker' role,
     * obtains the worker's identifier via getId(), and adds the worker to the appropriate status-specific
     * registry as well as the main items storage.
     *
     * Behavior and side effects:
     * - Validates input is a Worker instance with the 'worker' role and throws if not.
     * - Retrieves the worker ID using $item->getId().
     * - Adds the worker to the $this->items array indexed by the worker ID.
     * - Depending on the worker's status (UNASSIGNED, ASSIGNED, or TERMINATED), adds the worker
     *   to the corresponding status-specific array ($this->unassigned or $this->assigned).
     * - If the worker's status is TERMINATED, it is added to the $this->assigned array.
     * - Updates the total default rate by adding the default rate of the added worker.
     * - This method does not perform additional actions beyond updating the container's internal
     *   structures.
     *
     * @param mixed $item Worker instance to add to the container
     *
     * @throws InvalidArgumentException If the provided $item is not a Worker instance with the 'worker' role
     *
     * @return void
     */
    public function add($item): void
    {
        if (!Role::isWorker($item)) {
            throw new InvalidArgumentException("Only users with the 'worker' role can be added as project workers.");
        }

        $id = $item->getId();
        $status = $item->getStatus();
        switch ($status) {
            case WorkerStatus::UNASSIGNED:
                $this->unassigned[$id] = $item;
                break;
            case WorkerStatus::ASSIGNED:
                $this->assigned[$id] = $item;
                break;
            case WorkerStatus::TERMINATED:
                $this->assigned[$id] = $item;
                break;
        }
        $this->items[$id] = $item;
        $this->totalDefaultRate += $item->getDefaultRate();
    }

    /**
     * Removes a Worker instance from the container.
     *
     * This method enforces that the provided argument is a Worker instance, obtains the worker's
     * identifier via getId(), and removes the worker entry from the main items storage as well
     * as from any status-specific registries managed by the container.
     *
     * Behavior and side effects:
     * - Validates input is an instance of Worker and throws if not.
     * - Retrieves the worker ID using $item->getId().
     * - Unsets the worker entry from $this->items indexed by the worker ID.
     * - Unsets the worker entry from status-specific arrays: $this->unassigned, $this->assigned,
     *   and $this->terminated.
     * - Unsetting non-existent keys is a no-op (no error is thrown if the worker ID is not present).
     * - Updates the total default rate by subtracting the default rate of the removed worker.
     * - This method does not perform additional cleanup (e.g., terminating running tasks or freeing
     *   external resources) beyond removing references from the container's internal structures.
     *
     * @param mixed $item Worker instance to remove from the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Worker
     *
     * @return void
     */
    public function remove($item): void
    {
        if (!$item instanceof Worker) {
            throw new InvalidArgumentException('Only Worker instances can be removed from WorkerContainer.');
        }

        $id = $item->getId();
        $status = $item->getStatus();
        switch ($status) {
            case WorkerStatus::UNASSIGNED:
                unset($this->unassigned[$id]);
                break;
            case WorkerStatus::ASSIGNED:
                unset($this->assigned[$id]);
                break;
            case WorkerStatus::TERMINATED:
                unset($this->assigned[$id]);
                break;
        }
        unset($this->items[$id]);
        $this->totalDefaultRate -= $item->getDefaultRate();
    }

    /**
     * Checks if a Worker instance is present in the container.
     *
     * This method verifies whether the provided Worker instance exists in the container's
     * internal structures, based on its ID and status. The container maintains separate
     * registries for workers based on their statuses: unassigned, assigned, and terminated.
     *
     * Behavior and checks:
     * - Validates that the input is an instance of Worker and throws an exception if not.
     * - Retrieves the worker ID using $item->getId().
     * - Checks if the worker ID exists in the main items storage ($this->items).
     * - Depending on the worker's status (retrieved via $item->getStatus()), checks the
     *   corresponding status-specific registry ($this->unassigned, $this->assigned, or
     *   $this->terminated) to confirm the worker's presence.
     * - Returns true if the worker is found in both the main items storage and the
     *   appropriate status-specific registry; otherwise, returns false.
     *
     * @param mixed $item Worker instance to check for presence in the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Worker
     *
     * @return bool True if the Worker instance is present in the container, false otherwise
     */
    public function contains($item): bool
    {
        if (!$item instanceof Worker) {
            throw new InvalidArgumentException('Only Worker instances can be checked in WorkerContainer.');
        }

        $id = $item->getId();
        $isPresentInAll = isset($this->items[$id]);

        $status = $item->getStatus();
        switch ($status) {
            case WorkerStatus::UNASSIGNED:
                return isset($this->unassigned[$id]) && $isPresentInAll;
            case WorkerStatus::ASSIGNED:
                return isset($this->assigned[$id]) && $isPresentInAll;
            case WorkerStatus::TERMINATED:
                return isset($this->terminated[$id]) && $isPresentInAll;
            default:
                return false;
        }
    }

    /**
     * Returns the collection of unassigned workers stored in this container.
     *
     * This method exposes the internal unassigned array without modifying it.
     * The exact element shape depends on how the container is populated:
     * - Worker objects when domain objects are stored
     * - Integer IDs when only identifiers are stored
     * - Associative arrays when raw worker data is stored
     *
     * Consumers should treat the returned array as read-only (do not modify it
     * expecting the container to be updated) unless explicitly documented otherwise.
     *
     * @return array<int|object|array> Array of unassigned workers. Each element can be:
     *      - Worker object representing an unassigned worker
     *      - int identifier of a worker
     *      - array associative array with worker data
     */
    public function getUnassigned(): array
    {
        return $this->unassigned;
    }

    /**
     * Returns the array of items assigned to this worker container.
     *
     * This accessor provides a snapshot of the container's current assigned collection.
     * The returned array is a copy of the internal storage (PHP arrays use copy-on-write),
     * therefore modifying the returned array will not affect the container's internal state.
     * Use the container's mutation methods to modify assignments.
     *
     * Each element in the returned array represents an assigned item and may be:
     * - int: an assignment identifier
     * - string: an assignment key or slug
     * - object: a domain model representing the assignment
     * - array: an associative array with assignment data
     *
     * @return array<int, mixed> Array of assigned items
     */
    public function getAssigned(): array
    {
        return $this->assigned;
    }

    /**
     * Returns the list of terminated entries from the container.
     *
     * This method provides access to the container's internal terminated collection:
     * - Returns a shallow copy of the internal $terminated array (modifying the returned array will not modify the container's internal state).
     * - The array may be empty if no entries have been terminated.
     * - Each element represents a terminated worker entry; elements may be identifiers (int|string) or objects/arrays depending on how entries are stored.
     * - The original insertion order is preserved in the returned array.
     *
     * @return array<int, mixed> Array of terminated entries (identifiers, objects, or arrays) — empty if none
     */
    public function getTerminated(): array
    {
        return $this->terminated;
    }

    /**
     * Returns an array of workers for the given status.
     *
     * This method selects and returns the internal collection corresponding to the provided WorkerStatus:
     * - WorkerStatus::UNASSIGNED => unassigned workers
     * - WorkerStatus::ASSIGNED => assigned workers
     * - WorkerStatus::TERMINATED => terminated workers
     * - If an unrecognized status is provided, an empty array is returned.
     *
     * @param WorkerStatus $status The status to filter workers by. Expected values:
     *      - WorkerStatus::UNASSIGNED
     *      - WorkerStatus::ASSIGNED
     *      - WorkerStatus::TERMINATED
     *
     * @return array Array of workers matching the provided status. Returns an empty array if no workers are present for the given status.
     */
    public function getByStatus(WorkerStatus $status): array
    {
        return match ($status) {
            WorkerStatus::UNASSIGNED => $this->unassigned,
            WorkerStatus::ASSIGNED => $this->assigned,
            WorkerStatus::TERMINATED => $this->terminated,
            default => []
        };
    }

    /**
     * Retrieves the total default rate of all workers in the container.
     *
     * This method returns the cumulative default rate calculated from all
     * Worker instances currently stored in the container.
     *
     * Behavior and side effects:
     * - Returns the pre-calculated total default rate stored in $this->totalDefaultRate.
     * - The value reflects the sum of default rates of all workers added to the container.
     * - This method does not modify any internal state or properties of the container.
     *
     * @return float The total default rate of all workers in the container.
     */
    public function getTotalDefaultRate(): float
    {
        return $this->totalDefaultRate;
    }

    /**
     * Returns counts of all workers grouped by their status.
     *
     * This method provides an associative array representing a snapshot of worker counts
     * organized by status. It is intended to give callers an easy way to inspect
     * how many workers exist for each status:
     * - Keys are status identifiers (e.g. string names like "unassigned", "assigned", "terminated"
     *   or numeric status IDs depending on the application's convention)
     * - Values are integers representing the number of workers for that status
     *
     * @return array<string,int> Associative array mapping status identifiers to worker counts
     */
    public function countAll(): array
    {
        return [
            WorkerStatus::UNASSIGNED->value    => count($this->unassigned),
            WorkerStatus::ASSIGNED->value      => count($this->assigned),
            WorkerStatus::TERMINATED->value    => count($this->terminated),
        ];
    }

    /**
     * Returns the count of workers for a specific status.
     *
     * This method retrieves the number of workers that match the provided WorkerStatus.
     * It checks the corresponding internal array based on the status and returns
     * the count of workers in that category.
     *
     * @param WorkerStatus $status The status to count workers for. Expected values:
     *      - WorkerStatus::UNASSIGNED
     *      - WorkerStatus::ASSIGNED
     *      - WorkerStatus::TERMINATED
     *
     * @return int The count of workers with the specified status.
     */
    public function countByStatus(WorkerStatus $status): int
    {
        return match ($status) {
            WorkerStatus::UNASSIGNED    => count($this->unassigned),
            WorkerStatus::ASSIGNED      => count($this->assigned),
            WorkerStatus::TERMINATED    => count($this->terminated),
        };
    }

    /**
     * Reverses the order of the assigned items.
     *
     * This method performs the following steps:
     * - Reverses the order of the $this->assigned array.
     * - Updates the object's assigned property with the reversed array.
     * - Returns the updated array of assigned items.
     *
     * @return array The reversed array of assigned items.
     */
    public function reverseAssigned(): array
    {
        return $this->assigned = array_reverse($this->assigned, true);
    }

    /**
     * Reverses the internal unassigned items list.
     *
     * This method performs the following steps:
     * - Reverses the order of the $this->unassigned array.
     * - Assigns the reversed array back to the $this->unassigned property.
     * - Returns the reversed array for immediate use by the caller.
     *
     * @throws UnexpectedValueException If the unassigned property is not an array.
     *
     * @return array The reversed unassigned items.
     */
    public function reverseUnassigned(): array
    {
        return $this->unassigned = array_reverse($this->unassigned, true);
    }

    /**
     * Reverses the internal terminated container.
     *
     * This method performs the following steps:
     * - Reverses the order of the internal $this->terminated array.
     * - Assigns the reversed array back to $this->terminated to update internal state.
     * - Returns the updated terminated array for further use.
     *
     * @return array The reversed terminated array.
     */
    public function reverseTerminated(): array
    {
        return $this->terminated = array_reverse($this->terminated, true);
    }

    /**
     * Converts all workers in the container to an array representation.
     *
     * This method iterates over all workers and converts each to an array:
     * - Calls toArray() on each Worker instance
     * - Preserves the original order of workers
     *
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     * @return array<int, array<string, mixed>> Array of workers where each worker is represented as an associative array
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $workersArray = [];
        foreach ($this->items as $worker) {
            $workersArray[] = $worker->toArray($useSnakeCase);
        }
        return $workersArray;
    }

    /**
     * Creates a WorkerContainer instance from an array of worker data.
     *
     * This static factory method takes an array of worker data and converts each element
     * into a Worker object using the Worker::fromArray method. It then creates and returns
     * a new WorkerContainer containing these Worker objects.
     *
     * @param array $data Array of worker data arrays, where each element is an instance of Worker 
     *              or an array containing the necessary data to create a Worker instance
     * @return WorkerContainer New WorkerContainer instance containing Worker objects created from the provided data
     */
    public static function fromArray(array $data): WorkerContainer
    {
        $workers = new self();
        foreach ($data as $workerData) {
            if ($workerData instanceof Worker) {
                $workers->add($workerData);
            } else {
                $workers->add(Worker::fromArray($workerData));
            }
        }
        return $workers;
    }
}
