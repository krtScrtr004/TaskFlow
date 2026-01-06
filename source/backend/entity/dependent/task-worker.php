<?php

namespace App\Dependent;

use App\Container\JobTitleContainer;
use App\Core\UUID;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Exception\ValidationException;
use App\Validator\ResourceValidator;
use DateTime;

class TaskWorker extends Worker
{
    private float $estimatedHours;
    private float $actualHours;

    private ResourceValidator $resourceValidator;

    /**
     * Constructs a TaskWorker instance.
     *
     * This constructor initializes a TaskWorker object with the provided parameters.
     * It validates the estimated and actual hours using the ResourceValidator.
     * If validation fails, a ValidationException is thrown with the relevant error messages.
     *
     * @param int|null $id Worker's ID.
     * @param UUID|null $publicId Public identifier.
     * @param string $firstName Worker's first name.
     * @param string|null $middleName Worker's middle name.
     * @param string $lastName Worker's last name.
     * @param Gender $gender Worker's gender.
     * @param DateTime $birthDate Worker's birth date.
     * @param JobTitleContainer $jobTitles Worker's job titles.
     * @param string $contactNumber Worker's contact number.
     * @param string $email Worker's email address.
     * @param string|null $bio Worker's biography.
     * @param string|null $profileLink Worker's profile link.
     * @param DateTime $createdAt Worker's creation timestamp.
     * @param WorkerStatus|null $status Worker's worker status (default is UNASSIGNED).
     * @param float $estimatedHours Worker's estimated hours assigned (default is WORKER_HOURS_MIN).
     * @param float $actualHours Worker's actual hours worked (default is 0.0).
     * @param string|null $password Worker's password (optional).
     * @param DateTime|null $confirmedAt Worker's confirmation timestamp (optional).
     * @param DateTime|null $deletedAt Worker's deletion timestamp (optional).
     * @param array $additionalInfo Additional information (optional).
     * 
     * @throws ValidationException If validation of hours fails.
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

        // Task worker-specific properties
        ?WorkerStatus $status = WorkerStatus::UNASSIGNED,
        float $estimatedHours = WORKER_HOURS_MIN,
        float $actualHours = 0.0,

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
            status: $status,
            password: $password,
            confirmedAt: $confirmedAt,
            deletedAt: $deletedAt,
            additionalInfo: $additionalInfo
        );

        $this->resourceValidator = new ResourceValidator();
        $this->resourceValidator->validateHoursAssigned($estimatedHours);
        if ($actualHours > 0) 
            $this->resourceValidator->validateHoursAssigned($actualHours);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                "Task Worker Validation Failed",
                $this->resourceValidator->getErrors()
            );
        }

        $this->estimatedHours = $estimatedHours || WORKER_HOURS_MIN;
        $this->actualHours = $actualHours;
        $this->role = Role::TASK_WORKER;
    }

    // GETTERS

    /**
     * Gets the estimated hours assigned to the task worker.
     *
     * @return float The estimated hours.
     */
    public function getEstimatedHours(): float
    {
        return $this->estimatedHours;
    }

    /**
     * Gets the actual hours worked by the task worker.
     *
     * @return float The actual hours.
     */
    public function getActualHours(): float
    {
        return $this->actualHours;
    }

    // SETTERS

    /**
     * Gets the actual hours worked by the task worker.
     *
     * @return float The actual hours.
     */
    public function setEstimatedHours(float $hours): void
    {
        $this->resourceValidator->validateHoursAssigned($hours);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                "Invalid Estimated Hours",
                $this->resourceValidator->getErrors()
            );
        }
        $this->estimatedHours = $hours;
    }

    /**
     * Sets the actual hours worked by the task worker.
     *
     * @return float The actual hours.
     */
    public function setActualHours(float $hours): void
    {
        $this->resourceValidator->validateHoursAssigned($hours);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                "Invalid Actual Hours",
                $this->resourceValidator->getErrors()
            );
        }
        $this->actualHours = $hours;
    }

    // OTHER METHODS (UTILITY)

    /**
     * Creates a partial TaskWorker object from an associative array.
     *
     * This static method constructs a partial TaskWorker object using the provided
     * associative array. It normalizes the array keys to camelCase and utilizes the
     * parent Worker::createPartial() method to handle base worker properties.
     * TaskWorker-specific properties are then extracted and set on the partial object.
     *
     * @param array $data The associative array containing TaskWorker data.
     *      - id: int Task Worker ID.
     *      - publicId: UUID Public identifier.
     *      - firstName: string First name.
     *      - middleName: string|null Middle name.
     *      - lastName: string Last name.
     *      - gender: Gender User's gender.
     *      - birthDate: DateTime Birth date.
     *      - jobTitles: JobTitleContainer Job titles.
     *      - contactNumber: string Contact number.
     *      - email: string Email address.
     *      - bio: string|null Biography.
     *      - profileLink: string|null Profile link.
     *      - createdAt: DateTime Creation timestamp.
     *      - confirmedAt: DateTime|null Confirmation timestamp.
     *      - deletedAt: DateTime|null Deletion timestamp.
     *      - additionalInfo: array Additional information.
     *      - estimatedHours: float Estimated hours assigned.
     *      - actualHours: float Actual hours worked.
     * 
     * @return static The constructed partial TaskWorker object.
     */
    public static function createPartial(array $data): static
    {
        $data = normalizeArrayKeysToCamelCase($data);

        /** @var static $partial */ // tell IDE this is the called class (silences false positive)
        $partial = parent::createPartial($data);

        $partial->setEstimatedHours((float) ($data['estimatedHours'] ?? WORKER_HOURS_MIN));
        if (isset($data['actualHours'])) {
            $partial->setActualHours((float) $data['actualHours']);
        }

        return $partial;
    }

    /**
     * Converts the TaskWorker object to an associative array.
     *
     * This method converts the TaskWorker object into an associative array representation.
     * It includes an option to use snake_case for the keys instead of camelCase.
     *
     * @param bool $useSnakeCase Whether to use snake_case for keys. Default is false (camelCase).
     * 
     * @return array The associative array representation of the TaskWorker.
     *      - All base worker properties from parent::toArray()
     *      - estimatedHours: The estimated hours assigned to the task worker.
     *      - actualHours: The actual hours worked by the task worker.
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $parentArray = parent::toArray($useSnakeCase);
        $taskWorkerArray = [
            'estimatedHours' => $this->estimatedHours,
            'actualHours' => $this->actualHours,
        ];
        return array_merge($parentArray, $taskWorkerArray);
    }

    /**
     * Creates a TaskWorker object from an associative array.
     *
     * This static method constructs a TaskWorker object from the provided associative array.
     * It normalizes the array keys to camelCase and utilizes the parent Worker::fromArray()
     * method to handle base worker properties. TaskWorker-specific properties are then
     * extracted and used to create the TaskWorker instance.
     *
     * @param array $data The associative array containing TaskWorker data.
     * 
     * @return static The constructed TaskWorker object.
     */ 
    public static function fromArray(array $data): static
    {
        $data = normalizeArrayKeysToCamelCase($data);

        $worker = parent::fromArray($data);

        return new static(
            id: $worker->getId(),
            publicId: $worker->getPublicId(),
            firstName: $worker->getFirstName(),
            middleName: $worker->getMiddleName(),
            lastName: $worker->getLastName(),
            gender: $worker->getGender(),
            birthDate: $worker->getBirthDate(),
            jobTitles: $worker->getJobTitles(),
            contactNumber: $worker->getContactNumber(),
            email: $worker->getEmail(),
            bio: $worker->getBio(),
            profileLink: $worker->getProfileLink(),
            createdAt: $worker->getCreatedAt(),
            confirmedAt: $worker->getConfirmedAt(),
            deletedAt: $worker->getDeletedAt(),
            additionalInfo: $worker->getAdditionalInfo(),
            estimatedHours: $data['estimatedHours'] ?? 0.0,
            actualHours: $data['actualHours'] ?? 0.0,
        );
    }
}