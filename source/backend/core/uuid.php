<?php
use Ramsey\Uuid\Uuid as RamseyUuid;

class UUID
{
    private function __construct() {}

    public static function get(): RamseyUuid
    {
        return RamseyUuid::uuid4();
    }

    public static function fromString(string $uuidString): RamseyUuid
    {
        return RamseyUuid::fromString($uuidString);
    }

    public static function toString(RamseyUuid $uuid): string
    {
        return $uuid->toString();
    }

    public static function toBinary(RamseyUuid $uuid): string
    {
        // Returns 16-byte binary representation suitable for BINARY(16) storage
        return $uuid->getBytes();
    }

    public static function fromBinary(string $binaryUuid): RamseyUuid
    {
        // Convert 16-byte binary back to UUID object
        return RamseyUuid::fromBytes($binaryUuid);
    }
}