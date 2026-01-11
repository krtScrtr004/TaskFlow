<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\TaskResource;
use App\Dependent\TaskWorker;
use InvalidArgumentException;

class ResourceContainer extends Container
{
    private const RESOURCE_SUFFIX = '_r';
    private const TASK_WORKER_SUFFIX = '_tw';

    private WorkerContainer $workers;
    private array $resources = [];

    /**
     * Constructs the resource container and optionally populates it with provided resources.
     *
     * This constructor accepts an array of resources and invokes $this->add($resource) for each
     * element to register it with the container. Validation and normalization of each element is
     * delegated to the add() method; any exception thrown by add() will propagate out of the
     * constructor.
     *
     * Behavior and side effects:
     * - Iterates over $resources in the given order and calls $this->add($resource) for each item.
     * - Relies on add() to validate and store each resource; invalid items will cause add() to
     *   throw (exceptions propagate).
     * - Passing an empty array results in a no-op (container remains unchanged).
     * - Handling of duplicates or resource identity is determined by add() implementation.
     *
     * @param array $resources Array of resource items to add to the container
     *
     * @throws InvalidArgumentException If any provided element is rejected by add()
     *
     * @return void
     */
    public function __construct(array $resources = [])
    {
        $this->workers = new WorkerContainer();
        foreach ($resources as $resource) {
            $this->add($resource);
        }
    }

    /**
     * Adds a resource or task worker to the container.
     *
     * This method accepts either a TaskResource or TaskWorker instance and adds it to the
     * container. The item is identified using a unique identifier constructed from its ID
     * and type suffix. If the item is not of the expected types, an InvalidArgumentException
     * is thrown.
     *
     * @param mixed $item The TaskResource or TaskWorker instance to add
     *
     * @throws InvalidArgumentException If $item is not a TaskResource or TaskWorker instance
     *
     * @return void
     */
    public function add(mixed $item): void
    {
        if (!$item instanceof TaskResource && !$item instanceof TaskWorker) 
            throw new InvalidArgumentException('Only TaskResource or TaskWorker instances can be added to ResourceContainer.');

        if ($item instanceof TaskResource)
            $this->resources[$this->buildId($item)] = $item;
        else
            $this->workers->add($item);
        $this->items[$this->buildId($item)] = $item;
    }

    /**
     * Removes a resource or task worker from the container.
     *
     * This method accepts either a TaskResource or TaskWorker instance and removes it from
     * the container. The item is identified using a unique identifier constructed from its
     * ID and type suffix. If the item is not of the expected types, an InvalidArgumentException
     * is thrown.
     *
     * @param mixed $item The TaskResource or TaskWorker instance to remove
     *
     * @throws InvalidArgumentException If $item is not a TaskResource or TaskWorker instance
     *
     * @return void
     */
    public function remove(mixed $item): void
    {
        if (!$item instanceof TaskResource && !$item instanceof TaskWorker)
            throw new InvalidArgumentException('Only TaskResource or TaskWorker instances can be removed from ResourceContainer.');

        if ($item instanceof TaskResource)
            unset($this->resources[$this->buildId($item)]);
        else
            $this->workers->remove($item);
        unset($this->items[$this->buildId($item)]);
    }

    /**
     * Checks if a resource or task worker is present in the container.
     *
     * This method accepts either a TaskResource or TaskWorker instance and checks if it
     * exists in the container. The item is identified using a unique identifier constructed
     * from its ID and type suffix. If the item is not of the expected types, an
     * InvalidArgumentException is thrown.
     *
     * @param mixed $item The TaskResource or TaskWorker instance to check
     *
     * @throws InvalidArgumentException If $item is not a TaskResource or TaskWorker instance
     *
     * @return bool True if the item is present in the container, false otherwise
     */
    public function contains(mixed $item): bool
    {
        if (!$item instanceof TaskResource && !$item instanceof TaskWorker)
            throw new InvalidArgumentException('Only TaskResource or TaskWorker instances can be checked in ResourceContainer.');

        $id = $this->buildId($item);
        $isPresent = $item instanceof TaskResource
            ? isset($this->resources[$id])
            : $this->workers->contains($item);
        return isset($this->items[$id]) && $isPresent;
    }

    /**
     * Retrieves the resources stored in the resource container.
     *
     * This method returns the resources contained in the ResourceContainer. The caller
     * can specify whether to receive the resources as a ResourceContainer instance or
     * as a plain array of TaskResource instances.
     *
     * @param bool $useArray If true, returns an array of TaskResource instances; if false, returns a ResourceContainer
     *
     * @return ResourceContainer|array The resources in the specified format
     */
    public function getResources(bool $useArray = false): ResourceContainer|array
    {
        return $useArray ? $this->resources : self::fromArray($this->resources);
    }

    /**
     * Retrieves the worker container stored in the resource container.
     *
     * @return WorkerContainer The WorkerContainer instance contained in the resource container
     */
    public function getWorkers(): WorkerContainer
    {
        return $this->workers;
    }

    /**
     * Sets the worker container for the resource container.
     *
     * @param WorkerContainer $workers The WorkerContainer instance to set
     *
     * @return void
     */
    public function setWorkers(WorkerContainer $workers): void
    {
        $this->workers = $workers;
    }

    /**
     * Sets the resources for the resource container.
     *
     * @param ResourceContainer|array $resources The resources to set, either as a ResourceContainer or an array of TaskResource instances
     * 
     * @return void
     */
    public function setResources(ResourceContainer|array $resources): void
    {
        $this->resources = $resources instanceof ResourceContainer
            ? $resources->getResources()
            : $resources;
    }

    /**
     * Counts the number of resources stored in the container.
     *
     * @return int The count of Resource instances in the container
     */
    public function countResources(): int
    {
        return count($this->resources);
    }

    /**
     * Counts the number of task workers stored in the container.
     *
     * @return int The count of TaskWorker instances in the container
     */
    public function countWorkers(): int
    {
        return $this->workers->count();
    }

    /**
     * Converts the container's items to an associative array.
     *
     * This method iterates over all items in the container and converts each one to an array
     * representation by calling its toArray() method. The resulting arrays are collected into
     * an associative array, where each key is a unique identifier constructed from the item's
     * ID and type suffix.
     *
     * @param bool $useSnakeCase Whether to use snake_case keys in the output arrays
     *
     * @return array Associative array of item arrays, keyed by unique identifiers
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $resources = [];
        foreach ($this->items as $item) {
            $resources[$this->buildId($item)] = $item->toArray($useSnakeCase);
        }
        return $resources;
    }

    /**
     * Creates a ResourceContainer from an array of resources.
     *
     * This static method accepts an array of resources, which can be either TaskResource
     * or TaskWorker instances, or associative arrays representing their data. It constructs
     * a ResourceContainer by converting each element to the appropriate object type and
     * adding it to the container.
     *
     * @param array $data Array of resources (TaskResource, TaskWorker, or associative arrays)
     *
     * @return ResourceContainer The constructed ResourceContainer instance
     */
    public static function fromArray(array $data): mixed
    {
        $container = new self();
        foreach ($data as $resourceData) {
            if ($resourceData instanceof TaskResource || $resourceData instanceof TaskWorker)
                $container->add($resourceData);
            elseif (is_array($resourceData))
                $container->add(TaskResource::fromArray($resourceData));
        }
        return $container;
    }

    /**
     * Builds a unique identifier for a resource or task worker.
     *
     * This private method constructs a unique identifier string for the given item
     * by appending a type-specific suffix to its ID. The suffix is determined based
     * on whether the item is a TaskResource or TaskWorker instance.
     *
     * @param mixed $item The TaskResource or TaskWorker instance
     *
     * @throws InvalidArgumentException If $item is not a TaskResource or TaskWorker instance
     *
     * @return string The unique identifier string
     */
    private function buildId(mixed $item): string
    {
        if ($item instanceof TaskResource)
            return (string) $item->getId() . self::RESOURCE_SUFFIX;
        elseif ($item instanceof TaskWorker)
            return (string) $item->getId() . self::TASK_WORKER_SUFFIX;
        else
            throw new InvalidArgumentException('Item must be an instance of TaskResource or TaskWorker.');
    }
}