<?php

namespace App\Core;

use App\Core\UUID;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;
use App\Entity\User;
use DateTime;

class Me extends User
{
    private static ?Me $me = null;

    /**
     * Instantiates or re-instantiates the singleton Me instance from a User object or an associative array.
     *
     * This method accepts either a populated User domain object or a raw associative array and normalizes
     * values to the types expected by the Me instance:
     *  - When given a User object, values are copied from its getters.
     *  - When given an array, the following conversions are applied:
     *      - publicId: converted to a UUID via UUID::fromString()
     *      - gender: converted to a Gender enum via Gender::from()
     *      - birthDate: string converted to DateTime (nullable)
     *      - role: converted to a Role enum via Role::from()
     *      - jobTitles: comma-separated string is split and wrapped in a JobTitleContainer
     *      - createdAt: string converted to DateTime
     *
     * @param User|array $data User domain object or associative array containing user data with keys:
     *      - id: int User ID
     *      - publicId: string|UUID Public identifier
     *      - firstName: string User's first name
     *      - middleName: string|null User's middle name
     *      - lastName: string User's last name
     *      - gender: string|Gender User's gender
     *      - birthDate: string|DateTime|null User's birth date
     *      - role: string|Role User's role
     *      - jobTitles: string|array|JobTitleContainer Comma-separated string or container of job titles
     *      - contactNumber: string|null User's contact number
     *      - email: string User's email address
     *      - bio: string|null User's biography
     *      - profileLink: string|null User's profile link
     *      - createdAt: string|DateTime Timestamp when the user was created
     *      - additionalInfo: mixed|null Optional additional information
     *
     * @return void Sets the internal Me instance (self::$me); does not return a value.
     */
    public static function instantiate(User|array $data): void
    {
        // Allow re-instantiation to update the Me instance with new data
        if ($data instanceof User) {
            self::$me = new self(
                id: $data->getId(),
                publicId: $data->getPublicId(),
                firstName: $data->getFirstName(),
                middleName: $data->getMiddleName(),
                lastName: $data->getLastName(),
                gender: $data->getGender(),
                birthDate: $data->getBirthDate(),
                role: $data->getRole(),
                jobTitles: $data->getJobTitles(),
                contactNumber: $data->getContactNumber(),
                email: $data->getEmail(),
                bio: $data->getBio(),
                profileLink: $data->getProfileLink(),
                createdAt: $data->getCreatedAt(),
                additionalInfo: $data->getAdditionalInfo()
            );
        } else {
            self::$me = new self(
                id: $data['id'],
                publicId: UUID::fromString($data['publicId']),
                firstName: $data['firstName'],
                middleName: $data['middleName'] ?? null,
                lastName: $data['lastName'],
                gender: Gender::from($data['gender']),
                birthDate: isset($data['birthDate']) ? new DateTime($data['birthDate']) : null,
                role: Role::from($data['role']),
                jobTitles: new JobTitleContainer(explode(',', $data['jobTitles'] ?? '')),
                contactNumber: $data['contactNumber'],
                email: $data['email'],
                bio: $data['bio'] ?? null,
                profileLink: $data['profileLink'] ?? null,
                createdAt: new DateTime($data['createdAt']),
                additionalInfo: $data['additionalInfo'] ?? null
            );
        }
    }

    /**
     * Returns the current singleton instance or null.
     *
     * Retrieves the instance stored in the static $me property without creating or
     * modifying it. Use this to obtain the existing instance of the class if one
     * has been set, otherwise null will be returned.
     *
     * @return self|null The stored instance of this class, or null if none is set.
     */
    public static function getInstance(): ?self
    {
        return self::$me ?? null;
    }

    /**
     * Clears the cached current user instance.
     *
     * Resets the internal singleton/cache for the current "me" user so that the application
     * no longer holds a reference to it. This is typically used when the current user signs out
     * or when you need to force a fresh reload of the user data.
     *
     * Behavior:
     * - Sets the static property self::$me to null.
     * - Subsequent accesses that lazy-initialize "me" will recreate the instance.
     * - Idempotent: safe to call multiple times.
     *
     * @return void
     */
    public static function destroy(): void
    {
        self::$me = null;
    }
}
