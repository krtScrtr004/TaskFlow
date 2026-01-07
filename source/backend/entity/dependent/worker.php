<?php

namespace App\Dependent;

use App\Abstract\User;
use App\Enumeration\Gender;
use App\Enumeration\WorkerStatus;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;
use App\Core\UUID;
use App\Exception\ValidationException;
use InvalidArgumentException;
use DateTime;

class Worker extends User
{
    protected float $defaultRate;
    protected WorkerStatus $status;

    /**
     * Constructs a Worker instance.
     *
     * This constructor initializes a Worker object by calling the parent User constructor
     * and setting Worker-specific properties such as defaultRate and status.
     * It also validates the defaultRate and status using UserValidator.
     *
     * @param int|null $id Worker's ID
     * @param UUID|null $publicId Public identifier
     * @param string $firstName Worker's first name
     * @param string|null $middleName Worker's middle name
     * @param string $lastName Worker's last name 
     * @param Gender $gender Worker's gender                  
     * @param DateTime $birthDate Worker's birth date
     * @param JobTitleContainer $jobTitles Worker's job titles
     * @param string $contactNumber Worker's contact number
     * @param string $email Worker's email address
     * @param string|null $bio Worker's biography
     * @param string|null $profileLink Worker's profile link
     * @param DateTime $createdAt Timestamp when the worker was created
     * @param float $defaultRate Worker's default hourly rate
     * @param WorkerStatus $status Worker's current status
     * @param string|null $password Worker's password (optional)
     * @param DateTime|null $confirmedAt Timestamp when the worker was confirmed (optional)
     * @param DateTime|null $deletedAt Timestamp when the worker was deleted (optional)
     * @param array $additionalInfo Optional additional information (default: empty array)
     * @throws ValidationException If validation of defaultRate or status fails
     * @return void
     */
    public function __construct(
        ?int $id,
        ?UUID $publicId,
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

        // Worker-specific properties
        float $defaultRate = DEFAULT_RATE_MIN,
        ?WorkerStatus $status = WorkerStatus::UNASSIGNED,

        // Optional properties
        ?string $password = null,
        ?DateTime $confirmedAt = null,
        ?DateTime $deletedAt = null,
        array $additionalInfo = [],
    ) {
        parent::__construct(
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

        $this->userValidator->validateDefaultRate($defaultRate);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException(
                "Worker Validation Failed", 
                $this->userValidator->getErrors()
            );
        }
        // Set role-based properties
        $this->role = Role::WORKER;
        $this->defaultRate = $defaultRate;
        $this->status = $status ?? WorkerStatus::UNASSIGNED;
    }

    // GETTERS 

    /**
     * Retrieves the worker as a User object.
     *
     * This method returns the current Worker instance
     * cast as a User object, allowing access to base user properties.
     *
     * @return User The User representation of the worker
     */
    public function getWorker(): User
    {
        return $this;
    }

    /**
     * Retrieves the worker's default hourly rate.
     *
     * This method returns the default rate assigned to the worker,
     * which is used for billing and compensation calculations.
     *
     * @return float The default hourly rate of the worker
     */
    public function getDefaultRate(): float
    {
        return $this->defaultRate;
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
     * Sets the worker's default hourly rate.
     *
     * This method updates the default rate for the worker after validating it:
     * - Uses UserValidator to check if the provided rate is valid
     * - Throws an exception if validation fails
     * - Updates the worker's default rate if validation passes
     *
     * @param float $defaultRate The new default hourly rate to set for the worker
     * @throws InvalidArgumentException If the provided rate is invalid
     * @return void
     */
    public function setDefaultRate(float $defaultRate): void
    {
        $this->userValidator->validateDefaultRate($defaultRate);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException(
                "Invalid Default Rate", 
                $this->userValidator->getErrors()
            );
        }
        $this->defaultRate = $defaultRate;
    }

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
        $this->userValidator->validateStatus($status);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException(
                "Invalid Status", 
                $this->userValidator->getErrors()
            );
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
     *      - createdAt: string|DateTime When the worker joined
     *      - confirmedAt: string|DateTime|null When the worker was confirmed (optional)
     *      - deletedAt: string|DateTime|null When the worker was deleted (optional)
     *      - additionalInfo: array (optional) Additional worker information
     * 
     * @return static New Worker instance created from provided data
     */
    public static function createPartial(array $data): static
    {
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        /** @var static $partial */ // tell IDE this is the called class (silences false positive)
        $partial = parent::createPartial($data);

        $partial->setDefaultRate($data['defaultRate'] ?? DEFAULT_RATE_MIN);

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
     * Converts this Worker instance to an array representation.
     *
     * This method extends the parent's toArray functionality by adding
     * the specific role identifier for Worker objects.
     *
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     * @return array Associative array containing all worker data including:
     *      - All base user properties from the parent class
     *      - role: string The role identifier set to 'worker'
     *      - defaultRate: float The worker's default hourly rate
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $parentArray = parent::toArray($useSnakeCase);
        $workerArray = [
            'role'          => Role::WORKER->value,
            'defaultRate'   => $this->defaultRate,
        ];
        return array_merge($parentArray, $workerArray);
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
     *      - createdAt: string|DateTime When the worker joined
     *      - confirmedAt: string|DateTime|null When the worker was confirmed (optional)
     *      - deletedAt: string|DateTime|null When the worker was deleted (optional)
     *      - additionalInfo: array (optional) Additional worker information
     * 
     * @return static New Worker instance created from provided data
     */
    public static function fromArray(array $data): static
    {
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        $user = parent::createPartial($data);

        $defaultRate = $data['defaultRate'] ?? DEFAULT_RATE_MIN;

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
            defaultRate: $defaultRate,
            status: $status,
            createdAt: $user->getCreatedAt(),
            confirmedAt: $user->getConfirmedAt(),
            deletedAt: $user->getDeletedAt(),
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
