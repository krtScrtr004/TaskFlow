<?php

namespace App\Interface;

use JsonSerializable;

interface Entity extends JsonSerializable {
    public function jsonSerialize(): array;

    /**
     * Converts the entity to an array representation.
     *
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     * @return array The entity data as an associative array
     */
    public function toArray(bool $useSnakeCase = false): array;

    public static function fromArray(array $data): self;
}

