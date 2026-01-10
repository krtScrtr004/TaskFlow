<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Resource;
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
        foreach ($resources as $resource) {
            $this->add($resource);
        }
    }

    /**
     * Adds a resource or task worker to the container.
     *
     * This method accepts either a Resource or TaskWorker instance and adds it to the container.
     * The item is identified using a unique identifier constructed from its ID and type suffix.
     * If the item is not of the expected types, an InvalidArgumentException is thrown.
     *
     * @param mixed $item The Resource or TaskWorker instance to add
     *
     * @throws InvalidArgumentException If $item is not a Resource or TaskWorker instance
     *
     * @return void
     */
    public function add(mixed $item): void
    {
        if (!$item instanceof Resource && !$item instanceof TaskWorker) 
            throw new InvalidArgumentException('Only Resource or TaskWorker instances can be added to ResourceContainer.');

        if ($item instanceof Resource)
            $this->resources[$this->buildId($item)] = $item;
        else
            $this->workers->add($item);
        $this->items[$this->buildId($item)] = $item;
    }

    /**
     * Removes a resource or task worker from the container.
     *
     * This method accepts either a Resource or TaskWorker instance and removes it from the
     * container. The item is identified using a unique identifier constructed from its ID
     * and type suffix. If the item is not of the expected types, an InvalidArgumentException
     * is thrown.
     *
     * @param mixed $item The Resource or TaskWorker instance to remove
     *
     * @throws InvalidArgumentException If $item is not a Resource or TaskWorker instance
     *
     * @return void
     */
    public function remove(mixed $item): void
    {
        if (!$item instanceof Resource && !$item instanceof TaskWorker)
            throw new InvalidArgumentException('Only Resource or TaskWorker instances can be removed from ResourceContainer.');

        if ($item instanceof Resource)
            unset($this->resources[$this->buildId($item)]);
        else
            $this->workers->remove($item);
        unset($this->items[$this->buildId($item)]);
    }

    /**
     * Checks if a resource or task worker is present in the container.
     *
     * This method accepts either a Resource or TaskWorker instance and checks if it
     * exists in the container. The item is identified using a unique identifier
     * constructed from its ID and type suffix. If the item is not of the expected
     * types, an InvalidArgumentException is thrown.
     *
     * @param mixed $item The Resource or TaskWorker instance to check
     *
     * @throws InvalidArgumentException If $item is not a Resource or TaskWorker instance
     *
     * @return bool True if the item is present in the container, false otherwise
     */
    public function contains(mixed $item): bool
    {
        if (!$item instanceof Resource && !$item instanceof TaskWorker)
            throw new InvalidArgumentException('Only Resource or TaskWorker instances can be checked in ResourceContainer.');

        $id = $this->buildId($item);
        $isPresent = $item instanceof Resource
            ? isset($this->resources[$id])
            : $this->workers->contains($item);
        return isset($this->items[$id]) && $isPresent;
    }

    /**
     * Retrieves all resources stored in the container.
     *
     * @return array Array of Resource instances contained in the resource container
     */
    public function getResources(): array
    {
        return $this->resources;
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
     * Creates a ResourceContainer from an array of resource data.
     *
     * This static method accepts an array of resource data, where each element is either
     * a Resource instance or an associative array representing a resource. It constructs
     * a ResourceContainer by adding each resource to it. If an element is an array, it
     * is converted to a Resource instance using Resource::fromArray().
     *
     * @param array $data Array of resource data (Resource instances or associative arrays)
     *
     * @return ResourceContainer The constructed ResourceContainer
     */
    public static function fromArray(array $data): mixed
    {
        $container = new self();
        foreach ($data as $resourceData) {
            if ($resourceData instanceof Resource)
                $container->add($resourceData);
            elseif (is_array($resourceData))
                $container->add(Resource::fromArray($resourceData));
        }
        return $container;
    }

    /**
     * Builds a unique identifier for a resource or task worker.
     *
     * This private method constructs a unique identifier string for the given item
     * by appending a type-specific suffix to its ID. The suffix is determined based
     * on whether the item is a Resource or TaskWorker instance.
     *
     * @param mixed $item The Resource or TaskWorker instance
     *
     * @throws InvalidArgumentException If $item is not a Resource or TaskWorker instance
     *
     * @return string The unique identifier string
     */
    private function buildId(mixed $item): string
    {
        if ($item instanceof Resource)
            return (string) $item->getId() . self::RESOURCE_SUFFIX;
        elseif ($item instanceof TaskWorker)
            return (string) $item->getId() . self::TASK_WORKER_SUFFIX;
        else
            throw new InvalidArgumentException('Item must be an instance of Resource or TaskWorker.');
    }
}