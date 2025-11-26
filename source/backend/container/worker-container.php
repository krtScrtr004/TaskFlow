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

    /**
     * Constructs the container and populates it with Worker instances.
     *
     * This constructor accepts an array of workers and adds each element to the container
     * via the add() method. It validates that every element is an instance of Worker
     * and will throw an InvalidArgumentException when an invalid element is encountered.
     *
     * @param array|Worker[] $workers Array of Worker instances to register in the container.
     *      - Each element MUST be an instance of Worker.
     *
     * @throws InvalidArgumentException If any element of $workers is not an instance of Worker.
     */
    public function __construct(array $workers = [])
    {
        foreach ($workers as $worker) {
            if (!($worker instanceof Worker)) {
                throw new InvalidArgumentException("All elements of workers array must be instances of Worker.");
            }
            $this->add($worker);
        }
    }

    /**
     * Adds a worker to the container.
     *
     * Validates that the provided object represents a worker using Role::isWorker().
     * On success the worker is stored in the container's main items collection and
     * placed into a status-specific collection according to the worker's current status:
     * - WorkerStatus::UNASSIGNED  => stored in $this->unassigned
     * - WorkerStatus::ASSIGNED    => stored in $this->assigned
     * - WorkerStatus::TERMINATED  => stored in $this->terminated
     *
     * The worker is keyed by the identifier returned from getId(). If an entry with
     * the same id already exists it will be overwritten.
     *
     * @param object $worker Worker object expected to provide:
     *      - getId(): int|string   Unique worker identifier used as array key
     *      - getStatus(): string|WorkerStatus  Current status used to select status array
     *
     * @throws InvalidArgumentException If the provided object is not a worker (Role::isWorker returns false)
     *
     * @return void
     */
    public function add($worker): void
    {
        if (!Role::isWorker($worker)) {
            throw new InvalidArgumentException("Only users with the 'worker' role can be added as project workers.");
        }
        
        $workerId = $worker->getId();
        $this->items[$workerId] = $worker;
        
        // Store in status-specific array based on worker status
        $status = $worker->getStatus();
        if ($status === WorkerStatus::UNASSIGNED) {
            $this->unassigned[$workerId] = $worker;
        } elseif ($status === WorkerStatus::ASSIGNED) {
            $this->assigned[$workerId] = $worker;
        } elseif ($status === WorkerStatus::TERMINATED) {
            $this->terminated[$workerId] = $worker;
        }
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
        
        $workerId = $item->getId();
        unset($this->items[$workerId]);
        
        // Remove from status-specific arrays
        unset($this->unassigned[$workerId]);
        unset($this->assigned[$workerId]);
        unset($this->terminated[$workerId]);
    }

    /**
     * Determines whether a given Worker instance is present in this container.
     *
     * This method enforces the expected type and then checks membership across
     * the container's internal collections:
     * - Validates that the provided item is a Worker instance
     * - Uses the Worker's identifier (getId()) to check presence in:
     *   - $this->unassigned (workers not yet assigned)
     *   - $this->assigned (workers currently assigned)
     *   - $this->terminated (workers that have been terminated)
     *
     * @param Worker $item Worker instance to check for membership (must implement getId())
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Worker
     *
     * @return bool True if a worker with the same id exists in any of the internal collections, false otherwise
     */
    public function contains($item): bool
    {
        if (!$item instanceof Worker) {
            throw new InvalidArgumentException('Only Worker instances can be checked in WorkerContainer.');
        }
        return isset($this->unassigned[$item->getId()]) 
            || isset($this->assigned[$item->getId()]) 
            || isset($this->terminated[$item->getId()]);
    }

    /**
     * Returns the first Worker from the container across all internal lists.
     *
     * This method merges the container's internal collections and returns the first element
     * in the merged sequence, preserving the merge order:
     * - unassigned workers are searched first
     * - then assigned workers
     * - then terminated workers
     *
     * Behavior notes:
     * - If one or more internal arrays are empty they are skipped.
     * - The method operates on a merged copy and does not modify the original arrays.
     * - If no workers are present in any list the method returns null.
     * - The method expects each list to contain Worker instances; behavior is undefined for other types.
     *
     * @return Worker|null The first Worker found in the merged lists, or null if none exists.
     */
    public function first(): ?Worker
    {
        $allItems = array_merge($this->unassigned, $this->assigned, $this->terminated);
        return reset($allItems) ?: null;
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
        return match($status) {
            WorkerStatus::UNASSIGNED => $this->unassigned,
            WorkerStatus::ASSIGNED => $this->assigned,
            WorkerStatus::TERMINATED => $this->terminated,
        };
    }

    /**
     * Retrieves a Worker instance by key from the internal worker containers.
     *
     * This method checks the internal containers in the following order and returns
     * the first matching Worker it finds:
     * - unassigned: Workers that have not yet been assigned
     * - assigned: Workers that are currently assigned to tasks
     * - terminated: Workers that have been terminated
     *
     * The method does not modify any container or change the worker's state.
     *
     * @param int|string $key The key used to look up the Worker. This should match the array key type
     *                        used in the internal containers (int or string).
     *
     * @return Worker|null The Worker instance if found in any container; otherwise null.
     */
    public function get(int|string $key): ?Worker
    {
        return $this->unassigned[$key] ?? 
            $this->assigned[$key] ?? 
            $this->terminated[$key] ?? 
            null;
    }

    /**
     * Retrieves all items assigned to or terminated by the worker.
     *
     * This method combines the assigned and terminated items into a single array:
     * - Merges the $assigned array containing currently assigned items
     * - Merges the $terminated array containing items that have been terminated
     *
     * @return array Combined array of assigned and terminated items
     */
    public function getItems(): array|Worker
    {
        return array_merge($this->unassigned, $this->assigned, $this->terminated);
    }

    public function getIterator(): Traversable
    {  
        return new ArrayIterator($this->getItems());
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
