<?php

namespace App\Container;

use App\Abstract\Container;
use App\Entity\TaskWorker;
use App\Entity\Worker;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use InvalidArgumentException;
use Traversable;
use ArrayIterator;

class WorkerContainer extends Container
{
    /** @var array<string,array> workers by status value then id */
    private array $byStatus = [];

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
     * @throws InvalidArgumentException If the provided $item is not a Worker instance with the 'worker' or 'task worker' role
     *
     * @return void
     */
    public function add($item): void
    {
        if (!$item instanceof Worker && !$item instanceof TaskWorker) {
            throw new InvalidArgumentException('Only Worker or TaskWorker instances can be added from WorkerContainer.');
        }

        $id = $item->getId();
        $status = $item->getStatus();

        $this->byStatus[$status->value][$id] = $item;
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
        if (!$item instanceof Worker && !$item instanceof TaskWorker) {
            throw new InvalidArgumentException('Only Worker or TaskWorker instances can be removed from WorkerContainer.');
        }

        $id = $item->getId();
        $status = $item->getStatus();

        unset($this->byStatus[$status->value][$id]);
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
        if (!$item instanceof Worker && !$item instanceof TaskWorker) {
            throw new InvalidArgumentException('Only Worker or TaskWorker instances can be checked from WorkerContainer.');
        }

        $id = $item->getId();
        return isset($this->items[$id]);
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
        return $this->byStatus[$status->value] ?? [];
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
        return count($this->byStatus[$status->value] ?? []);
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
            WorkerStatus::UNASSIGNED->value    => count($this->byStatus[WorkerStatus::UNASSIGNED->value] ?? []),
            WorkerStatus::ASSIGNED->value      => count($this->byStatus[WorkerStatus::ASSIGNED->value] ?? []),
            WorkerStatus::TERMINATED->value    => count($this->byStatus[WorkerStatus::TERMINATED->value] ?? []),
        ];
    }
    
    /**
     * Clears all workers for the given status and updates total default rate and master items.
     *
     * @param WorkerStatus $status
     * @return void
     */
    public function clearByStatus(WorkerStatus $status): void
    {
        if (!isset($this->byStatus[$status->value])) {
            return;
        }

        $ids = array_keys($this->byStatus[$status->value]);
        foreach ($ids as $id) {
            if (isset($this->items[$id])) {
                $this->totalDefaultRate -= $this->items[$id]->getDefaultRate();
                unset($this->items[$id]);
            }
        }

        unset($this->byStatus[$status->value]);
    }

    /**
     * Clears all workers from the container.
     *
     * This method removes all workers from the container by clearing the internal
     * arrays that store unassigned, assigned, and terminated workers.
     * After calling this method, the container will be empty.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->byStatus = [];
        $this->items = [];
        $this->totalDefaultRate = DEFAULT_RATE_MIN;
    }

    /**
     * Reverses the order of workers for the given status.
     *
     * This method reverses the sequence of workers stored in the container's
     * internal array for the specified status.
     *
     * @param WorkerStatus $status The status of workers to reverse.
     *
     * @throws Exception For unexpected errors during array reversal.
     *
     * @return array The reversed array of workers after modification.
     */
    public function reverseByStatus(WorkerStatus $status): array
    {
        return $this->byStatus[$status->value] = array_reverse($this->byStatus[$status->value]);
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
