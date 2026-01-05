<?php

namespace App\Core;

use App\Core\UUID;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;
use App\Abstract\User;
use App\Dependent\ProjectManager;
use App\Dependent\Worker;
use DateTime;

class Me extends User
{
    private static ?Me $me = null;

    /**
     * Constructs a Me instance.
     *
     * This constructor initializes a Me object by calling the parent User constructor.
     *
     * @param int $id User ID
     * @param UUID $publicId Public identifier
     * @param string $firstName User's first name
     * @param string|null $middleName User's middle name
     * @param string $lastName User's last name
     * @param Gender $gender User
     * @param DateTime $birthDate User's birth date
     * @param JobTitleContainer $jobTitles Container of job titles
     * @param Role $role User's role
     * @param string $contactNumber User's contact number
     * @param string $email User's email address
     * @param string|null $bio User's biography
     * @param string|null $profileLink User's profile link
     * @param DateTime $createdAt Timestamp when the user was created
     * @param array $additionalInfo Optional additional information (default: empty array)
     * 
     * @return void
     */
    public static function instantiate(ProjectManager|Worker|array $data): void
    {
        // Allow re-instantiation to update the Me instance with new data
        self::$me =  ($data instanceof ProjectManager)
            ? new self(
                id: $data->getId(),
                publicId: $data->getPublicId(),
                firstName: $data->getFirstName(),
                middleName: $data->getMiddleName(),
                lastName: $data->getLastName(),
                gender: $data->getGender(),
                birthDate: $data->getBirthDate(),
                jobTitles: $data->getJobTitles(),
                role: $data->getRole(),
                contactNumber: $data->getContactNumber(),
                email: $data->getEmail(),
                bio: $data->getBio(),
                profileLink: $data->getProfileLink(),
                createdAt: $data->getCreatedAt(),
                additionalInfo: $data->getAdditionalInfo()
            )
            : self::$me = new self(
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
