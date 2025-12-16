<?php

namespace App\Core;

use App\Exception\ValidationException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;

class UUID
{
    private UuidInterface $uuid;

    /**
     * Initializes the instance with a UUID.
     *
     * The constructor accepts a UuidInterface implementation and assigns it to the internal identifier property.
     * It is private to enforce controlled instantiation (for example via static factory methods or deserialization),
     * ensuring the object is always created with a valid UUID and preventing direct external construction.
     *
     * @param UuidInterface $uuid UuidInterface instance representing the unique identifier for this object
     */
    private function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Generate and return a new random (version 4) UUID.
     *
     * This static factory method uses Ramsey\Uuid to create a cryptographically
     * secure random UUID (v4) and wraps it in the project's UUID value object.
     *
     * @return UUID A new UUID instance containing a randomly generated version 4 UUID.
     *
     * @see \Ramsey\Uuid\Uuid::uuid4()
     */
    public static function get(): UUID
    {
        return new UUID(RamseyUuid::uuid4());
    }

    /**
     * Creates a UUID instance from a string representation.
     *
     * Parses the provided UUID string using Ramsey\Uuid and wraps the result in the project's UUID value object.
     * This method centralizes validation and conversion of textual UUIDs into the application's UUID type.
     * If the input is not a valid UUID string, a ValidationException is thrown.
     *
     * @param string $uuidString UUID string (e.g. "550e8400-e29b-41d4-a716-446655440000")
     *
     * @return self New UUID instance created from the provided string
     *
     * @throws ValidationException If the provided string cannot be parsed as a valid UUID
     */
    public static function fromString(string $uuidString): UUID
    {
        try {
            return new UUID(RamseyUuid::fromString($uuidString));
        } catch (InvalidUuidStringException $e) {
            throw new ValidationException('Invalid UUID string provided.');
        }
    }

    /**
     * Returns the string representation of the provided UUID wrapper.
     *
     * This method extracts the inner UUID object from the given UUID wrapper
     * and returns its canonical textual representation:
     * - Retrieves the inner `uuid` object from the provided `UUID` instance
     * - Calls `toString()` on the inner UUID to obtain the standard string form
     *
     * @param UUID $id UUID wrapper instance containing the inner uuid object
     * @return string Canonical string representation of the UUID
     */
    public static function toString(UUID $id): string
    {
        return $id->uuid->toString();
    }

    /**
     * Converts a UUID instance to a 16-byte binary string.
     *
     * This method extracts the raw 16-byte (128-bit) representation from the
     * provided UUID wrapper and returns it in a compact binary form suitable for
     * storage in a BINARY(16) database column.
     *
     * - Returns exactly 16 bytes
     * - Output is not human-readable; use a corresponding conversion (e.g. fromBinary)
     *   to reconstruct the UUID object or a textual representation
     *
     * @param UUID $id UUID instance to convert
     *
     * @return string 16-byte binary representation suitable for BINARY(16) storage
     */
    public static function toBinary(UUID $id): string
    {
        // Returns 16-byte binary representation suitable for BINARY(16) storage
        return $id->uuid->getBytes();
    }

    /**
     * Creates a UUID instance from a 16-byte binary representation.
     *
     * This method converts a binary UUID (16 bytes) into the project's UUID object:
     * - Parses the binary data using Ramsey\Uuid\Uuid::fromBytes()
     * - Wraps the resulting Ramsey UUID in the local UUID class
     * - Catches low-level parsing exceptions and normalizes them to a ValidationException
     *
     * @param string $binaryUuid 16-byte binary string representing a UUID (e.g. produced by Ramsey\Uuid\Uuid::getBytes()).
     *
     * @return UUID The newly created UUID instance wrapping the parsed Ramsey UUID.
     *
     * @throws ValidationException If the provided binary string is not a valid UUID representation.
     */
    public static function fromBinary(string $binaryUuid): UUID
    {
        try {
            // Convert 16-byte binary back to UUID object
            return new UUID(RamseyUuid::fromBytes($binaryUuid));
        } catch (InvalidUuidStringException $e) {
            throw new ValidationException('Invalid UUID string provided.');
        }
    }
    
    /**
     * Creates a UUID instance from a raw hexadecimal UUID string.
     *
     * This method expects a 32-character hexadecimal string representation of a UUID
     * (i.e. without hyphens). It will insert hyphens at the canonical UUID positions
     * and delegate to Ramsey\Uuid to parse the resulting UUID string, then wrap the
     * result in the local UUID value object.
     *
     * Behavior:
     * - Inserts hyphens into the hex string at positions: 8-4-4-4-12 to form the canonical UUID format
     * - Uses Ramsey\Uuid::fromString to parse the canonical UUID string
     * - Wraps the parsed Ramsey UUID in the local UUID value object
     * - Catches invalid UUID parse errors and rethrows them as a ValidationException
     *
     * @param string $hexString 32-character hexadecimal UUID string (without hyphens, case-insensitive)
     *
     * @return UUID New UUID instance created from the provided hex string
     *
     * @throws ValidationException If the provided string is not a valid 32-character hex UUID or cannot be parsed
     */
    public static function fromHex(string $hexString): UUID
    {
        try {
            // Convert hex string back to UUID object
            return new UUID(RamseyUuid::fromString(
                // Insert hyphens into the hex string to match UUID format
                substr($hexString, 0, 8) . '-' .
                substr($hexString, 8, 4) . '-' .
                substr($hexString, 12, 4) . '-' .
                substr($hexString, 16, 4) . '-' .
                substr($hexString, 20)
            ));
        } catch (InvalidUuidStringException $e) {
            throw new ValidationException('Invalid UUID string provided.');
        }
    }
}