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

    /**
     * Initializes UUID handling and validator for the class.
     *
     * This constructor configures the UUID factory and prepares the RFC 4122 validator:
     * - Creates a UuidFactory instance and registers it as the global UUID factory via Uuid::setFactory()
     * - Instantiates an Rfc4122Validator and assigns it to $this->validator for subsequent UUID validation
     *
     * @return void
     */
    public function __construct() {
        $factory = new UuidFactory();
        Uuid::setFactory($factory);
        $this->validator = new Rfc4122Validator();
    }

    /**
     * Validates a UUID value and records any validation errors.
     *
     * This method accepts either a raw UUID string or a MyUUID object and ensures
     * the value conforms to the expected UUID format:
     * - Converts a MyUUID instance to string using MyUUID::toString()
     * - Uses the internal validator ($this->validator->validate) to check format
     * - On validation failure, captures the InvalidArgumentException message
     *   and appends it to $this->errors
     *
     * @param string|MyUUID $uuid UUID to validate; may be a UUID string or a MyUUID instance
     *
     * @return void
     *
     * @see MyUUID::toString()
     */
    public function validateUuid(string|MyUUID $uuid): void {
        try {
            $id = is_string($uuid) ? $uuid : MyUUID::toString($uuid);
            if (!$this->validator->validate((string)$id)) {
                throw new InvalidArgumentException('Invalid UUID format.');
            }
        } catch (InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}