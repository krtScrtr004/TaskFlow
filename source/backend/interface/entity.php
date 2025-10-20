<?php

namespace App\Interface;

use JsonSerializable;

interface Entity extends JsonSerializable {
    public function jsonSerialize(): array;

    public function toArray(): array;

    public static function fromArray(array $data): self;
}

