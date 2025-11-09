<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Worker;
use App\Entity\User;
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

    public function __construct(array $workers = [])
    {
        foreach ($workers as $worker) {
            if (!($worker instanceof Worker)) {
                throw new InvalidArgumentException("All elements of workers array must be instances of Worker.");
            }
            $this->add($worker);
        }
    }

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

    public function contains($item): bool
    {
        if (!$item instanceof Worker) {
            throw new InvalidArgumentException('Only Worker instances can be checked in WorkerContainer.');
        }
        return isset($this->unassigned[$item->getId()]) 
            || isset($this->assigned[$item->getId()]) 
            || isset($this->terminated[$item->getId()]);
    }

    public function first(): ?Worker
    {
        $allItems = array_merge($this->unassigned, $this->assigned, $this->terminated);
        return reset($allItems) ?: null;
    }

    /**
     * Gets workers with 'unassigned' status.
     *
     * @return array Array of Worker instances with unassigned status
     */
    public function getUnassigned(): array
    {
        return $this->unassigned;
    }

    /**
     * Gets workers with 'assigned' status.
     *
     * @return array Array of Worker instances with assigned status
     */
    public function getAssigned(): array
    {
        return $this->assigned;
    }

    /**
     * Gets workers with 'terminated' status.
     *
     * @return array Array of Worker instances with terminated status
     */
    public function getTerminated(): array
    {
        return $this->terminated;
    }

    /**
     * Gets workers filtered by a specific status.
     *
     * @param WorkerStatus $status The status to filter by
     * @return array Array of Worker instances matching the specified status
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
     * Retrieves a Worker instance by its key.
     *
     * This method attempts to fetch a Worker object from the container using the provided key.
     * If the key does not exist in the container, it returns null.
     *
     * @param int|string $key The key associated with the Worker instance.
     *
     * @return Worker|null The Worker instance if found, or null if the key does not exist.
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
