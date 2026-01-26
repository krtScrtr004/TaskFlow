<?php

namespace App\Entity;

use App\Abstract\User;
use App\Core\UUID;
use App\Enumeration\Gender;
use DateTime;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;

class ProjectManager extends User
{

    /**
     * Constructs a ProjectManager dependent entity.
     *
     * This constructor sets the dependent role to Role::PROJECT_MANAGER and delegates the
     * remainder of initialization to the parent constructor, forwarding all provided identity,
     * contact, job and metadata parameters.
     *
     * Behavior and side effects:
     * - Sets $this->role to Role::PROJECT_MANAGER.
     * - Calls parent::__construct(...) with the provided arguments to perform field assignment,
     *   validation and any additional initialization implemented in the parent.
     * - Any exceptions thrown by the parent constructor (e.g., validation failures) are propagated.
     * - Does not perform further side effects beyond setting the role and delegating to parent.
     *
     * @param int $id Internal integer identifier
     * @param UUID $publicId Public UUID identifier
     * @param string $firstName First name of the project manager
     * @param string $lastName Last name of the project manager
     * @param Gender $gender Gender value for the project manager
     * @param DateTime $birthDate Date of birth
     * @param JobTitleContainer $jobTitles Container of job titles/roles
     * @param string $contactNumber Primary contact phone number
     * @param string $email Primary email address
     *
     * OPTIONAL / WITH DEFAULT VALUES:
     * @param string|null $middleName Optional middle name (nullable)
     * @param string|null $bio Optional biography or description (nullable)
     * @param string|null $profileLink Optional URL to a profile (nullable)
     * @param string|null $password Optional hashed password (nullable)
     * @param DateTime|null $createdAt Optional creation timestamp (nullable)
     * @param DateTime|null $confirmedAt Optional confirmation timestamp (nullable)
     * @param DateTime|null $deletedAt Optional deletion timestamp (nullable)
     * @param array $additionalInfo Optional associative array of additional metadata (default: [])
     *
     * @return void
     */
    public function __construct(
        int $id, 
        UUID $publicId, 
        string $firstName, 
        string $lastName, 
        Gender $gender, 
        DateTime $birthDate, 
        JobTitleContainer $jobTitles, 
        string $contactNumber, 
        string $email, 

        // Optional
        ?string $middleName = null, 
        ?string $bio = null, 
        ?string $profileLink = null, 
        ?string $password = null, 
        ?DateTime $createdAt, 
        ?DateTime $confirmedAt = null, 
        ?DateTime $deletedAt = null, 
        array $additionalInfo = [],
    ) {
        $this->role = Role::PROJECT_MANAGER;
        return parent::__construct(
            id: $id, 
            publicId: $publicId, 
            firstName: $firstName, 
            middleName: $middleName, 
            lastName: $lastName, 
            gender: $gender, 
            birthDate: $birthDate, 
            jobTitles: $jobTitles, 
            contactNumber: $contactNumber, 
            email: $email, 
            bio: $bio, 
            profileLink: $profileLink, 
            createdAt: $createdAt, 
            confirmedAt: $confirmedAt, 
            deletedAt: $deletedAt, 
            password: $password, 
            additionalInfo: $additionalInfo
        );
    }
}
