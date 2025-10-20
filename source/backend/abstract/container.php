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

    public function getItems(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
