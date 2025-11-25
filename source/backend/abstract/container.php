<?php

namespace App\Abstract;

use IteratorAggregate;
use ArrayIterator;
use Traversable;
use Countable;
use JsonSerializable;

abstract class Container implements IteratorAggregate, Countable, JsonSerializable
{
    protected array $items = [];

    abstract public function add(mixed $item): void;

    abstract public function remove(mixed $item): void;

    abstract public static function fromArray(array $data): mixed;

    abstract public function contains(mixed $item): bool;

    /**
     * Returns the first element from the container.
     *
     * This method moves the internal array pointer to the first element and returns its value.
     * - Uses reset($this->items) to obtain the first element and reset the pointer.
     * - If the container is empty, null is returned.
     * - Important: because the implementation uses the `?: null` idiom, any falsy first element
     *   (false, 0, '', [], null) will be normalized to null — a stored falsy value cannot be
     *   distinguished from an empty container.
     *
     * @return mixed|null The first item in the container, or null if the container is empty or the first item is falsy.
     */
    public function first(): mixed
    {
        return reset($this->items) ?: null;
    }

    /**
     * Retrieves an item from the container by its integer key.
     *
     * Returns the stored value for the given key if present, or null when the key
     * does not exist in the container. The value is returned as-is (no cloning or
     * type conversion is performed).
     *
     * This method:
     * - Accepts only integer keys which serve as identifiers/indices in the container
     * - Returns any type that may have been stored (scalar, array, object, callable, etc.)
     * - Does not throw on missing keys — use the null return to detect absence
     * - Provides O(1) average access time for array-backed containers
     *
     * @param int $key Integer key identifying the item to retrieve.
     *
     * @return mixed|null The value associated with the key, or null if the key is not found.
     */
    public function get(int $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    /**
     * Returns the items stored in this container.
     *
     * This method exposes the container's internal "items" value. The concrete
     * shape and type of the returned value depend on how items were populated:
     * - array: Indexed or associative array of items (e.g. array<int, mixed> or array<string, mixed>)
     * - Traversable/Iterator: An iterable object such as ArrayObject or a custom collection
     * - object: Any object that represents the stored payload
     * - null: No items have been set
     *
     * Consumers should check the returned type (e.g. is_array(), $items instanceof Traversable)
     * before operating on it. The method returns the raw stored value and does not
     * guarantee immutability or cloning of that value.
     *
     * @return mixed The container contents (array|Traversable|object|null|other), as stored internally
     */
    public function getItems(): mixed
    {
        return $this->items;
    }

    /**
     * Returns an iterator for traversing the container's items.
     *
     * This method exposes the container contents as a Traversable iterator:
     * - Wraps the internal $this->items array in an ArrayIterator
     * - Returns a new iterator instance to avoid exposing internal array references
     * - Enables usage with foreach and other iterator-aware code
     *
     * @return Traversable|ArrayIterator Iterator over the stored items
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Returns the number of elements contained in this container.
     *
     * This method uses PHP's native count() on the internal $items storage.
     * If $items implements Countable, that implementation will be used.
     * The returned value is a non-negative integer representing how many items
     * are currently stored in the container.
     *
     * @return int Number of items in the container
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns the container's items as an array.
     *
     * This method exposes the internal storage of the container:
     * - Preserves keys (string|int) and their associated values
     * - Returns a shallow copy of the internal array; modifying the returned array does not alter the container's internal state
     * - Useful for iteration, serialization, debugging, or converting to other representations
     *
     * @return array<string|int,mixed> Associative array of stored items where keys are item identifiers and values are the stored values
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Returns an array representation of the object for JSON serialization.
     *
     * This method delegates to toArray() to produce a structure suitable for
     * JSON encoding. It is intended to be used by json_encode() and other JSON
     * serializers. Any complex or non-scalar properties should be converted to
     * JSON-serializable types within toArray().
     *
     * @return array Associative array representing this container for JSON output
     * @see toArray()
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
