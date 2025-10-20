<?php

use Ramsey\Uuid\Rfc4122\Validator as Rfc4122Validator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

class UuidValidator {
    private Rfc4122Validator $validator;

    public function __construct() {
        $factory = new UuidFactory();
        Uuid::setFactory($factory);
        $this->validator = new Rfc4122Validator();
    }

    public function validateUuid(string|Uuid $uuid): bool {
        try {
            $id = is_string($uuid) ? Uuid::fromString($uuid) : $uuid;
            return $this->validator->validate($id);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}