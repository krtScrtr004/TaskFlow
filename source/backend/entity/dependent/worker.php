<?php

namespace App\Dependent;

use App\Entity\User;
use App\Enumeration\Gender;
use App\Enumeration\WorkerStatus;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;
use App\Core\UUID;
use App\Validator\UserValidator;
use InvalidArgumentException;
use DateTime;

class Worker extends User
{
    private WorkerStatus $status;

    /**
     * Constructor for the Worker class.
     *
     * Creates a new Worker instance with the specified attributes.
     * Workers extend the base User class with additional worker-specific properties
     * such as worker status.
     *
     * @param int|null $id The internal database identifier
     * @param UUID|null $publicId The public UUID identifier
     * @param string $firstName Worker's first name
     * @param string $middleName Worker's middle name
     * @param string $lastName Worker's last name
     * @param Gender $gender Worker's gender (enum)
     * @param DateTime $birthDate Worker's date of birth
     * @param JobTitleContainer $jobTitles Container for worker's job titles
     * @param string $contactNumber Worker's contact phone number
     * @param string $email Worker's email address
     * @param string|null $bio Worker's biography or description
     * @param string|null $profileLink Link to worker's profile
     * @param WorkerStatus $status Current status of the worker (enum)
     * @param DateTime $createdAt Timestamp when the worker was created
     * @param array $additionalInfo Optional array of additional information
     */
    public function __construct(
        ?int $id,
        ?UUID $publicId,
        string $firstName,
        string $middleName,
        string $lastName,
        Gender $gender,
        DateTime $birthDate,
        JobTitleContainer $jobTitles,
        string $contactNumber,
        string $email,
        ?string $bio,
        ?string $profileLink,
        WorkerStatus $status,
        DateTime $createdAt,
        array $additionalInfo = []
    ) {
        parent::__construct(
            id: $id,
            publicId: $publicId,
            firstName: $firstName,
            middleName: $middleName,
            lastName: $lastName,
            gender: $gender,
            birthDate: $birthDate,
            role: Role::WORKER,
            jobTitles: $jobTitles,
            contactNumber: $contactNumber,
            email: $email,
            bio: $bio,
            profileLink: $profileLink,
            createdAt: $createdAt,
            password: null,
            additionalInfo: $additionalInfo
        );
        $this->status = $status;
    }

    // GETTERS 

    public function getWorker(): User
    {
        return $this;
    }

    /**
     * Retrieves the worker's current status.
     *
     * This method returns the current WorkerStatus enum value representing
     * the worker's status in the system (e.g., ACTIVE, INACTIVE, ON_LEAVE).
     *
     * @return WorkerStatus The enum value representing the worker's current status
     */
    public function getStatus(): WorkerStatus
    {
        return $this->status;
    }

    // SETTERS

    /**
     * Sets the worker's status.
     *
     * This method updates the status of a worker after validating it:
     * - Uses UserValidator to check if the provided status is valid
     * - Throws an exception if validation fails
     * - Updates the worker's status if validation passes
     *
     * @param WorkerStatus $status The new status to set for the worker
     * @throws InvalidArgumentException If the provided status is invalid
     * @return void
     */
    public function setStatus(WorkerStatus $status): void
    {
        $validator = new UserValidator();
        $validator->validateStatus($status);
        if ($validator->hasErrors()) {
            throw new InvalidArgumentException('Invalid worker status provided.');
        }
        $this->status = $status;
    }

    // OTHER METHODS (UTILITY)

    /**
     * Creates a partial Worker instance from an array of data.
     *
     * This method initializes a Worker object using partial data, delegating base user creation
     * to User::createPartial and converting it to a Worker. It handles the worker's status field,
     * accepting either a string (converted to WorkerStatus enum) or a WorkerStatus instance.
     * If no status is provided, it defaults to WorkerStatus::UNASSIGNED.
     *
     * @param array $data Associative array containing worker data with the following keys:
     *      - id: int Worker ID
     *      - publicId: string|UUID|binary Public identifier
     *      - firstName: string Worker's first name
     *      - middleName: string Worker's middle name
     *      - lastName: string Worker's last name
     *      - gender: string|Gender Worker's gender
     *      - birthDate: string|DateTime Worker's birth date
     *      - role: string|Role Worker's role
     *      - jobTitles: array|JobTitleContainer Worker's job titles
     *      - contactNumber: string Worker's contact number
     *      - email: string Worker's email
     *      - profileLink: string Worker's profile link
     *      - status: string|WorkerStatus Worker's status
     *      - joinedDateTime: string|DateTime When the worker joined
     *      - additionalInfo: array (optional) Additional worker information
     * 
     * @return self New Worker instance created from provided data
     */
    public static function createPartial(array $data): Worker
    {
        $partial = User::createPartial($data)->toWorker();
        if (isset($data['status'])) {
            $partial->setStatus(
                is_string($data['status'])
                    ? WorkerStatus::tryFrom(trimOrNull($data['status']))
                    : $data['status']
            );
        } else {
            $partial->setStatus(WorkerStatus::UNASSIGNED);
        }
        return $partial;
    }
    
    /**
     * Converts a Worker instance to a User object.
     *
     * This method creates a new User object using the properties
     * of the provided Worker instance. The resulting User object
     * retains all relevant information from the Worker.
     *
     * @param Worker $worker The Worker instance to convert
     * @return User A new User instance created from the provided Worker
     */
    public static function toUser(Worker $worker): User
    {
        return new User(
            id: $worker->getId(),
            publicId: $worker->getPublicId(),
            firstName: $worker->getFirstName(),
            middleName: $worker->getMiddleName(),
            lastName: $worker->getLastName(),
            gender: $worker->getGender(),
            birthDate: $worker->getBirthDate(),
            role: Role::WORKER,
            jobTitles: $worker->getJobTitles(),
            contactNumber: $worker->getContactNumber(),
            email: $worker->getEmail(),
            bio: $worker->getBio(),
            profileLink: $worker->getProfileLink(),
            createdAt: $worker->getCreatedAt(),
            password: $worker->getPassword(),
            additionalInfo: $worker->getAdditionalInfo()
        );
    }

    /**
     * Creates a Worker instance from a User object.
     *
     * This method converts a User object to a Worker object after verifying
     * that the user has the Worker role. The resulting Worker instance
     * inherits most properties from the User object but initializes with
     * a predefined WorkerStatus.ASSIGNED status.
     *
     * @param User $user The User object to convert to a Worker
     * @throws InvalidArgumentException If the User does not have the Worker role
     * @return Worker A new Worker instance created from the provided User
     */
    public static function fromUser(User $user): Worker
    {
        if (!Role::isWorker($user)) {
            throw new InvalidArgumentException('User must have the Worker role to be converted to Worker.');
        }

        return new Worker(
            id: $user->getId(),
            publicId: $user->getPublicId(),
            firstName: $user->getFirstName(),
            middleName: $user->getMiddleName(),
            lastName: $user->getLastName(),
            gender: $user->getGender(),
            birthDate: $user->getBirthDate(),
            jobTitles: $user->getJobTitles(),
            contactNumber: $user->getContactNumber(),
            email: $user->getEmail(),
            bio: $user->getBio(),
            profileLink: $user->getProfileLink(),
            status: WorkerStatus::ASSIGNED,
            createdAt: $user->getCreatedAt(),
            additionalInfo: $user->getAdditionalInfo()
        );
    }

    /**
     * Converts this Worker instance to an array representation.
     *
     * This method extends the parent's toArray functionality by adding
     * the specific role identifier for Worker objects.
     *
     * @return array Associative array containing all worker data including:
     *      - All base user properties from the parent class
     *      - role: string The role identifier set to 'worker'
     */
    public function toArray(): array
    {
        $worker = parent::toArray();
        $worker['role'] = Role::WORKER->value;

        return $worker;
    }

    /**
     * Creates a Worker instance from an array of data.
     *
     * This method first creates a User object from the provided data
     * and then constructs a Worker object with the User properties
     * and Worker-specific properties.
     *
     * @param array $data Associative array containing worker data with following keys:
     *      - id: int User ID
     *      - publicId: string|UUID|binary Public identifier
     *      - firstName: string Worker's first name
     *      - middleName: string Worker's middle name
     *      - lastName: string Worker's last name
     *      - gender: string|Gender Worker's gender
     *      - birthDate: string|DateTime Worker's birth date
     *      - jobTitles: array|JobTitleContainer Worker's job titles
     *      - contactNumber: string Worker's contact number
     *      - email: string Worker's email address
     *      - bio: string Worker's biography
     *      - profileLink: string Worker's profile link
     *      - status: string|WorkerStatus Worker's status
     *      - joinedDateTime: string|DateTime When the worker joined
     *      - additionalInfo: array (optional) Additional worker information
     * 
     * @return self New Worker instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        $user = User::fromArray($data);

        $status = (is_string($data['status']))
            ? WorkerStatus::tryFrom(trimOrNull($data['status']))
            : $data['status'];

        return new Worker(
            id: $user->getId(),
            publicId: $user->getPublicId(),
            firstName: $user->getFirstName(),
            middleName: $user->getMiddleName(),
            lastName: $user->getLastName(),
            gender: $user->getGender(),
            birthDate: $user->getBirthDate(),
            jobTitles: $user->getJobTitles(),
            contactNumber: $user->getContactNumber(),
            email: $user->getEmail(),
            bio: $user->getBio(),
            profileLink: $user->getProfileLink(),
            status: $status,
            createdAt: $user->getCreatedAt(),
            additionalInfo: $user->getAdditionalInfo()
        );
    }

    /**
     * Serializes the current object to JSON.
     * 
     * Implements the JsonSerializable interface method which specifies
     * the data that should be serialized to JSON when json_encode() is called
     * on this object. This method delegates to the toArray() method to
     * convert the object to an associative array.
     * 
     * @return array Associative array representation of the object ready for JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}