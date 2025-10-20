<?php

namespace App\Validator;

use Ramsey\Uuid\Rfc4122\Validator as Rfc4122Validator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

use App\Core\UUID as MyUUID;
use App\Abstract\Validator;
use InvalidArgumentException;

class UuidValidator extends Validator {
    private Rfc4122Validator $validator;

    public function __construct() {
        $factory = new UuidFactory();
        Uuid::setFactory($factory);
        $this->validator = new Rfc4122Validator();
    }

    public function validateUuid(string|MyUUID $uuid): void {
        try {
            $id = is_string($uuid) ? MyUUID::fromString($uuid) : $uuid;
            if (!$this->validator->validate((string)$id)) {
                throw new InvalidArgumentException('Invalid UUID format.');
            }
        } catch (InvalidArgumentException $e) {
            $this->errors['uuid'] = $e->getMessage();
        }
    }

}