<?php

namespace App\Dependent;

use App\Abstract\User;
use App\Core\UUID;
use App\Enumeration\Gender;
use DateTime;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;

class ProjectManager extends User
{
    /**
     * Constructs a ProjectManager instance.
     *
     * This constructor initializes a ProjectManager by calling the parent User constructor
     * with the Role set to Role::PROJECT_MANAGER.
     *
     * @param int $id User ID
     * @param UUID $publicId Public identifier
     * @param string $firstName User's first name
     * @param string|null $middleName User's middle name
     * @param string $lastName User's last name
     * @param Gender $gender User's gender  
     * @param DateTime $birthDate User's birth date
     * @param JobTitleContainer $jobTitles Container of job titles
     * @param string $contactNumber User's contact number
     * @param string $email User's email address
     * @param string|null $bio User's biography
     * @param string|null $profileLink User's profile link
     * @param DateTime $createdAt Timestamp when the user was created
     * @param DateTime|null $confirmedAt Timestamp when the user was confirmed (optional)
     * @param DateTime|null $deletedAt Timestamp when the user was deleted (optional)
     * @param string|null $password User's password (optional)
     * @param array $additionalInfo Optional additional information (default: empty array)
     */
    public function __construct(
        int $id, 
        UUID $publicId, 
        string $firstName, 
        ?string $middleName, 
        string $lastName, 
        Gender $gender, 
        DateTime $birthDate, 
        JobTitleContainer $jobTitles, 
        string $contactNumber, 
        string $email, 
        ?string $bio, 
        ?string $profileLink, 
        DateTime $createdAt, 
        ?DateTime $confirmedAt = 
        null, ?DateTime $deletedAt = null, 
        ?string $password = null, 
        array $additionalInfo = []
    ) {
        return parent::__construct(
            $id, 
            $publicId, 
            $firstName, 
            $middleName, 
            $lastName, 
            $gender, 
            $birthDate, 
            Role::PROJECT_MANAGER, 
            $jobTitles, 
            $contactNumber, 
            $email, 
            $bio, 
            $profileLink, 
            $createdAt, 
            $confirmedAt, 
            $deletedAt, 
            $password, 
            $additionalInfo
        );
    }
}
