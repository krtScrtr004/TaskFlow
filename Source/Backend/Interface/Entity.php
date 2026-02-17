<?php

namespace App\Interface;

use JsonSerializable;

interface Entity extends JsonSerializable {
    
    /**
     * Specifies data which should be serialized to JSON.
     *
     * @return array The data to be serialized
     */
    public function jsonSerialize(): array;

    /**
     * Converts the entity to an array representation.
     *
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     * @return array The entity data as an associative array
     */
    public function toArray(bool $useSnakeCase = false): array;

    /**
     * Creates an entity instance from an associative array.
     *
     * @param array $data The data to create the entity from
     * @return self The created entity instance
     */
    public static function fromArray(array $data): self;
}

