<?php

namespace App\Core;

use App\Exception\ForbiddenException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;

class UUID
{
    private UuidInterface $uuid;

    private function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function get(): UUID
    {
        return new UUID(RamseyUuid::uuid4());
    }

    public static function fromString(string $uuidString): UUID
    {
        try {
            return new UUID(RamseyUuid::fromString($uuidString));
        } catch (InvalidUuidStringException $th) {
            throw new ForbiddenException('Invalid UUID string provided.');
        }
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
        try {
            // Convert 16-byte binary back to UUID object
            return new UUID(RamseyUuid::fromBytes($binaryUuid));
        } catch (InvalidUuidStringException $th) {
            throw new ForbiddenException('Invalid UUID string provided.');
        }
    }
}