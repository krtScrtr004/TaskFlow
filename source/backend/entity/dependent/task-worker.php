<?php

namespace App\Dependent;

use App\Container\JobTitleContainer;
use App\Core\UUID;
use App\Entity\ResourceType;
use App\Enumeration\Gender;
use App\Enumeration\ResourceTypeMapping;
use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Exception\ValidationException;
use App\Validator\ResourceValidator;
use DateTime;

class TaskWorker extends Worker
{
    private float $unitRate;
    private float $estimatedHour;
    private float $actualHour;

    private ResourceValidator $resourceValidator;

    /**
     * Constructs a TaskWorker instance.
     *
     * This constructor initializes a TaskWorker object with the provided parameters.
     * It validates the estimated and actual hours using the ResourceValidator.
     * If validation fails, a ValidationException is thrown with the relevant error messages.
     *
     * @param int $id Worker's ID.
     * @param UUID $publicId Public identifier.
     * @param string $firstName Worker's first name.
     * @param string|null $middleName Worker's middle name (optional).
     * @param string $lastName Worker's last name.
     * @param Gender $gender Worker's gender.
     * @param DateTime $birthDate Worker's birth date.
     * @param JobTitleContainer $jobTitles Worker's job titles.
     * @param string $contactNumber Worker's contact number.
     * @param string $email Worker's email address.
     * 
     * TASK WORKER SPECIFIC:
     * @param float $defaultRate Worker's default rate (default is DEFAULT_RATE_MIN).
     * @param float $unitRate Worker's unit rate (default is DEFAULT_RATE_MIN).
     * @param WorkerStatus $status Worker's worker status (default is UNASSIGNED).
     * @param float $estimatedHour Worker's estimated hours assigned (default is WORKER_HOURS_MIN).
     * @param float $actualHour Worker's actual hours worked (default is 0.0).
     * 
     * OPTIONAL / WITH DEFAULT VALUES:
     * @param string|null $bio Worker's biography (optional).
     * @param string|null $profileLink Worker's profile link (optional).
     * @param string|null $password Worker's password (optional).
     * @param DateTime|null $confirmedAt Worker's confirmation timestamp (optional).
     * @param DateTime|null $deletedAt Worker's deletion timestamp (optional).
     * @param array $additionalInfo Additional information (optional).
     * @param DateTime|null $createdAt Worker's creation timestamp (optional).
     * 
     * @throws ValidationException If validation of hours fails.
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

        // Task worker-specific properties
        float $defaultRate = DEFAULT_RATE_MIN,
        float $unitRate = DEFAULT_RATE_MIN,
        WorkerStatus $status = WorkerStatus::UNASSIGNED,
        float $estimatedHour = WORKER_HOURS_MIN,
        float $actualHour = 0.0,

        // Optional
        ?string $middleName = null,
        ?string $bio = null,
        ?string $profileLink = null,
        ?string $password = null,
        ?DateTime $confirmedAt = null,
        ?DateTime $deletedAt = null,
        array $additionalInfo = [],
        ?DateTime $createdAt = null,
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
            defaultRate: $defaultRate,
            profileLink: $profileLink,
            createdAt: $createdAt,
            status: $status,
            password: $password,
            confirmedAt: $confirmedAt,
            deletedAt: $deletedAt,
            additionalInfo: $additionalInfo
        );

        $this->resourceValidator = new ResourceValidator();
        $this->resourceValidator->validateUnitRate($unitRate);
        $this->resourceValidator->validateHoursAssigned($estimatedHour);
        if ($actualHour > 0) 
            $this->resourceValidator->validateHoursAssigned($actualHour);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                "Task Worker Validation Failed",
                $this->resourceValidator->getErrors()
            );
        }

        $this->unitRate = $unitRate;
        $this->estimatedHour = $estimatedHour;
        $this->actualHour = $actualHour;
        $this->role = Role::TASK_WORKER;
    }

    // GETTERS

    /**
     *  Gets the unit rate of the worker.
     * 
     * @return float The worker's unit rate
     */
    public function getUnitRate(): float
    {
        return $this->unitRate;
    }

    /**
     * Gets the estimated hours assigned to the task worker.
     *
     * @return float The estimated hours.
     */
    public function getEstimatedHours(): float
    {
        return $this->estimatedHour;
    }

    /**
     * Gets the actual hours worked by the task worker.
     *
     * @return float The actual hours.
     */
    public function getActualHours(): float
    {
        return $this->actualHour;
    }

    // SETTERS

    /**
     * Sets the unit rate of the worker.
     * 
     * @param float $unitRate The worker's unit rate
     * @throws ValidationException If the unit rate is invalid
     * @return void
     */
    public function setUnitRate(float $unitRate): void
    {
        $this->resourceValidator->validateUnitRate($unitRate);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                "Invalid Unit Rate",
                $this->resourceValidator->getErrors()
            );
        }
        $this->unitRate = $unitRate;
    }

    /**
     * Sets the estimated hours assigned to the task worker.
     *
     * @param float $hours The estimated hours.
     * @throws ValidationException If the estimated hours are invalid
     * @return void
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
        $this->estimatedHour = $hours;
    }

    /**
     * Sets the actual hours worked by the task worker.
     *
     * @param float $hours The actual hours.
     * @throws ValidationException If the actual hours are invalid
     * @return void
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
        $this->actualHour = $hours;
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
     *      - password: string|null Password.
     *      - bio: string|null Biography.
     *      - profileLink: string|null Profile link.
     *      - defaultRate: float Default rate.
     *      - unitRate: float Worker's unit rate.
     *      - status: WorkerStatus|null Worker's status.
     *      - createdAt: DateTime Creation timestamp.
     *      - confirmedAt: DateTime|null Confirmation timestamp.
     *      - deletedAt: DateTime|null Deletion timestamp.
     *      - additionalInfo: array Additional information.
     *      - estimatedHour: float Estimated hours assigned.
     *      - actualHour: float Actual hours worked.
     * 
     * @return static The constructed partial TaskWorker object.
     */
    public static function createPartial(array $data): static
    {
        $data = normalizeArrayKeysToCamelCase($data);

        /** @var static $partial */ // tell IDE this is the called class (silences false positive)
        $partial = parent::createPartial($data);

        $partial->setUnitRate((float) ($data['unitRate'] ?? DEFAULT_RATE_MIN));
        $partial->setEstimatedHours((float) ($data['estimatedHour'] ?? WORKER_HOURS_MIN));
        if (isset($data['actualHour'])) {
            $partial->setActualHours((float) $data['actualHour']);
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
     *      - estimatedHour: The estimated hours assigned to the task worker.
     *      - actualHour: The actual hours worked by the task worker.
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $parentArray = parent::toArray($useSnakeCase);
        $taskWorkerArray = [
            'unitRate'      => $this->unitRate,
            'estimatedHour' => $this->estimatedHour,
            'actualHour'    => $this->actualHour,
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
            password: $worker->getPassword(),
            bio: $worker->getBio(),
            profileLink: $worker->getProfileLink(),
            defaultRate: $worker->getDefaultRate(),
            unitRate: $data['unitRate'] ?? DEFAULT_RATE_MIN,
            status: $worker->getStatus(),
            createdAt: $worker->getCreatedAt(),
            confirmedAt: $worker->getConfirmedAt(),
            deletedAt: $worker->getDeletedAt(),
            additionalInfo: $worker->getAdditionalInfo(),
            estimatedHour: $data['estimatedHour'] ?? 0.0,
            actualHour: $data['actualHour'] ?? 0.0,
        );
    }
}