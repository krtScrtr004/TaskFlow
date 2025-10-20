<?php

namespace App\Core;

use Ramsey\Uuid\Uuid as RamseyUuid;

class UUID
{
    private RamseyUuid $uuid;

    private function __construct(RamseyUuid $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function get(): UUID
    {
        return new UUID(RamseyUuid::uuid4());
    }

    public static function fromString(string $uuidString): UUID
    {
        return new UUID(RamseyUuid::fromString($uuidString));
    }

    public static function toString(UUID $id): string
    {
        return $id->uuid->toString();
    }

    public static function toBinary(UUID $id): string
    {
        // Returns 16-byte binary representation suitable for BINARY(16) storage
        return $id->uuid->getBytes();
    }

    public static function fromBinary(string $binaryUuid): UUID
    {
        // Convert 16-byte binary back to UUID object
        return new UUID(RamseyUuid::fromBytes($binaryUuid));    
    }
}