<?php

namespace App\Entity;

use App\Container\ResourceContainer;
use App\Interface\Entity;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Dependent\TaskResource;
use App\Dependent\TaskWorker;
use App\Exception\ValidationException;
use App\Validator\ResourceValidator;
use App\Validator\UuidValidator;
use App\Validator\WorkValidator;
use DateTime;

class Task implements Entity
{
    private int $id;
    private UUID $publicId;
    private string $name;
    private ?string $description;
    private ?ResourceContainer $resources;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private TaskPriority $priority;
    private WorkStatus $status;
    private DateTime $createdAt;
    private array $additionalInfo;
    private float $estimatedCost;
    private float $actualCost;
    private ?string $budgetNote;

    protected WorkValidator $workValidator;
    protected ResourceValidator $resourceValidator; 

    /**
     * Constructor for the Task entity.
     *
     * @param int $id The internal ID of the task
     * @param UUID $publicId The public UUID of the task
     * @param string $name The name of the task
     * @param DateTime $startDateTime The start date and time of the task
     * @param DateTime $completionDateTime The expected completion date and time of the task
     * @param TaskPriority $priority The priority level of the task
     * @param WorkStatus $status The current status of the task
     * @param DateTime $createdAt The creation timestamp of the task
     * 
     * @param string|null $description Optional description of the task
     * @param WorkerContainer|null $workers Optional container of workers assigned to the task
     * @param ResourceContainer|null $resources Optional container of resources associated with the task
     * @param float $estimatedCost Optional estimated cost of the task
     * @param float $actualCost Optional actual cost of the task
     * @param string|null $budgetNote Optional budget note for the task
     * @param DateTime|null $actualCompletionDateTime Optional actual completion date and time of the task
     * @param array $additionalInfo Optional additional information related to the task
     * 
     * @throws ValidationException If any validation fails during property assignment
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        TaskPriority $priority,
        WorkStatus $status,
        DateTime $createdAt,

        // Optional parameters
        ?string $description = null,
        ?WorkerContainer $workers = null,
        ?ResourceContainer $resources = null,
        float $estimatedCost = DEFAULT_RATE_MIN,
        float $actualCost = DEFAULT_RATE_MIN,
        ?string $budgetNote = null,
        ?DateTime $actualCompletionDateTime = null,
        array $additionalInfo = [],
    ) {
        try {
            $this->workValidator = new WorkValidator();
            $this->workValidator->validateMultiple([
                'name'                  => $name,
                'description'           => $description,
                'estimatedUnit'         => $estimatedCost,
                'actualUnit'            => $actualCost,
                'startDateTime'         => $startDateTime,
                'completionDateTime'    => $completionDateTime
            ]);
            if ($this->workValidator->hasErrors()) {
                throw new ValidationException("Task validation failed", $this->workValidator->getErrors());
            }
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = trimOrNull($name);
        $this->description = trimOrNull($description);
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->priority = $priority;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->additionalInfo = $additionalInfo;
        $this->estimatedCost = $estimatedCost;
        $this->actualCost = $actualCost;
        $this->budgetNote = $budgetNote;

        $this->resources = $resources;
        if ($workers || $resources) {
            $this->resources = new ResourceContainer();
            if ($workers) {
                foreach ($workers as $worker) {
                    $this->resources->add($worker);
                }
            }
            if ($resources) {
                foreach ($resources as $resource) {
                    $this->resources->add($resource);
                }
            }
        }
    }

    // Getters

    /**
     * Gets the unique identifier of the task.
     *
     * @return int The internal ID of the task
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the public identifier of the task.
     *
     * @return UUID The UUID object representing the public ID
     */
    public function getPublicId(): UUID
    {
        return $this->publicId;
    }

    /**
     * Gets the name of the task.
     *
     * @return string The task's name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the description of the task.
     *
     * @return string|null The task's description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Gets all workers assigned to the task.
     *
     * @return WorkerContainer|null The container with the task's workers
     */
    public function getWorkers(): ?WorkerContainer
    {
        return $this->resources ? $this->resources->getWorkers() : null;
    }

    /**
     * Gets all resources associated with the task.
     *
     * @return ResourceContainer The container with the task's resources
     */
    public function getResources(): ?ResourceContainer
    {
        return $this->resources;
    }

    /**
     * Gets the task start date and time.
     *
     * @return DateTime The DateTime object representing when the task starts
     */
    public function getStartDateTime(): DateTime
    {
        return $this->startDateTime;
    }

    /**
     * Gets the expected completion date and time.
     *
     * @return DateTime The DateTime object representing the planned completion date
     */
    public function getCompletionDateTime(): DateTime
    {
        return $this->completionDateTime;
    }

    /**
     * Gets the actual completion date and time.
     *
     * @return DateTime|null The DateTime object representing when the task was completed, or null if not completed
     */
    public function getActualCompletionDateTime(): ?DateTime
    {
        return $this->actualCompletionDateTime;
    }

    /**
     * Gets the priority level of the task.
     *
     * @return TaskPriority The TaskPriority enum representing the task's priority
     */
    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    /**
     * Gets the current status of the task.
     *
     * @return WorkStatus The WorkStatus enum representing the task's status
     */
    public function getStatus(): WorkStatus
    {
        return $this->status;
    }

    /**
     * Gets the estimated cost of the task.
     *
     * @return float The estimated cost value
     */
    public function getEstimatedCost(): float
    {
        return $this->estimatedCost;
    }

    /**
     * Gets the actual cost of the task.
     *
     * @return float The actual cost value
     */
    public function getActualCost(): float
    {
        return $this->actualCost;
    }

    /**
     * Gets the budget note associated with the task.
     *
     * @return string|null The budget note, or null if none
     */
    public function getBudgetNote(): ?string
    {
        return $this->budgetNote;
    }

    /**
     * Gets the creation timestamp of the task.
     *
     * @return DateTime The DateTime object representing when the task was created
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets the additional information associated with the user.
     *
     * @param string $key Optional key to retrieve specific additional info
     * @return mixed Array containing all additional user information, specific info if key is provided, or null if key not found
     */
    public function getAdditionalInfo(string $key = ''): mixed
    {
        return trimOrNull(string: $key) 
            ? ($this->additionalInfo[$key] ?? null) 
            : $this->additionalInfo;
    }

    // Setters

    /**
     * Sets the task ID.
     *
     * @param int $id The task ID to set
     * @throws ValidationException If the ID is negative
     * @return void
     */
    public function setId(int $id): void
    {
        if ($id < 0) {
            throw new ValidationException("Invalid task ID");
        }
        $this->id = $id;
    }

    /**
     * Sets the task's public ID.
     *
     * @param UUID $publicId The UUID to set as public ID
     * @throws ValidationException If the Public ID is invalid
     * @return void
     */
    public function setPublicId(UUID $publicId): void
    {
        $uuidValidator = new UuidValidator();
        $uuidValidator->validateUuid($publicId);
        if ($uuidValidator->hasErrors()) {
            throw new ValidationException("Invalid public ID", $uuidValidator->getErrors());
        }
        $this->publicId = $publicId;
    }

    /**
     * Sets the task's name.
     *
     * @param string $name The name to set (3-255 characters)
     * @throws ValidationException If the name is invalid
     * @return void
     */
    public function setName(string $name): void
    {
        $this->workValidator->validateName(trim($name));
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid task name", $this->workValidator->getErrors());
        }
        $this->name = trimOrNull($name);
    }

    /**
     * Sets the task's description.
     *
     * @param string $description The description to set (5-500 characters, optional)
     * @throws ValidationException If the description is invalid
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->workValidator->validateDescription(trim($description));
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid task description", $this->workValidator->getErrors());
        }
        $this->description = trimOrNull($description);
    }

    /**
     * Sets the task workers.
     *
     * @param WorkerContainer $workers Container of workers to assign to the task
     * @return void
     */
    public function setWorkers(WorkerContainer $workers): void
    {
        if (!$this->resources) {
            $this->resources = new ResourceContainer();
        }
        $this->resources->setWorkers($workers);
    }

    /**
     * Sets the task resources.
     *
     * @param ResourceContainer $resources Container of resources to associate with the task
     * @return void
     */
    public function setResources(ResourceContainer $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * Sets the estimated cost of the task.
     *
     * @param float $estimatedCost The estimated cost to set
     * @throws ValidationException If the estimated cost is invalid
     * @return void
     */
    public function setEstimatedCost(float $estimatedCost): void
    {
        $this->resourceValidator->validateEstimatedUnit($estimatedCost);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException("Invalid estimated cost", $this->resourceValidator->getErrors());
        }
        $this->estimatedCost = $estimatedCost;
    }

    /**
     * Sets the actual cost of the task.
     *
     * @param float $actualCost The actual cost to set
     * @throws ValidationException If the actual cost is invalid
     * @return void
     */
    public function setActualCost(float $actualCost): void
    {
        $this->resourceValidator->validateActualUnit($actualCost);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException("Invalid actual cost", $this->resourceValidator->getErrors());
        }
        $this->actualCost = $actualCost;
    }

    /**
     * Sets the budget note for the task.
     *
     * @param string|null $budgetNote The budget note to set (optional)
     * @throws ValidationException If the budget note is invalid
     * @return void
     */
    public function setBudgetNote(?string $budgetNote): void
    {
        $this->workValidator->validateBudgetNote($budgetNote);
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid budget note", $this->workValidator->getErrors());
        }
        $this->budgetNote = $budgetNote;
    }

    /**
     * Sets the task start date and time.
     *
     * @param DateTime $startDateTime The start date and time to set (cannot be in the past)
     * @throws ValidationException If the start date is invalid or in the past
     * @return void
     */
    public function setStartDateTime(DateTime $startDateTime): void
    {
        $this->workValidator->validateStartDateTime($startDateTime);
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid start date", $this->workValidator->getErrors());
        }
        $this->startDateTime = $startDateTime;
    }

    /**
     * Sets the expected completion date and time.
     *
     * @param DateTime $completionDateTime The planned completion date and time to set (must be after start date)
     * @throws ValidationException If the completion date is invalid or not after start date
     * @return void
     */
    public function setCompletionDateTime(DateTime $completionDateTime): void
    {
        $this->workValidator->validateCompletionDateTime($completionDateTime, $this->startDateTime);
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid completion date", $this->workValidator->getErrors());
        }
        $this->completionDateTime = $completionDateTime;
    }

    /**
     * Sets the actual completion date and time.
     *
     * @param DateTime|null $actualCompletionDateTime The actual completion date and time, or null if not completed
     * @return void
     */
    public function setActualCompletionDateTime(?DateTime $actualCompletionDateTime): void
    {
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    /**
     * Sets the task priority.
     *
     * @param TaskPriority $priority The TaskPriority enum value to set
     * @return void
     */
    public function setPriority(TaskPriority $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Sets the task status.
     *
     * @param WorkStatus $status The WorkStatus enum value to set
     * @return void
     */
    public function setStatus(WorkStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Sets the task creation timestamp.
     *
     * @param DateTime $createdAt The creation timestamp to set
     * @throws ValidationException If the creation date is in the future
     * @return void
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        if ($createdAt > new DateTime()) {
            throw new ValidationException("Invalid creation date");
        }
        $this->createdAt = $createdAt;
    }

    /**
     * Summary of setAdditionalInfo
     * @param array $additionalInfo
     * @return void
     */
    public function setAdditionalInfo(array $additionalInfo): void
    {
        $this->additionalInfo = $additionalInfo;
    }

    // Other methods (Utility)

    /**
     * Adds a worker to the task.
     *
     * @param TaskWorker $worker The worker to add to the task
     * @return void
     */
    public function addWorker(TaskWorker $worker): void
    {
        if (!$this->resources) {
            $this->resources = new ResourceContainer();
        }
        $this->resources->add($worker);
    }

/**
     * Adds or updates a key-value pair in the task's additional information.
     *
     * This method stores custom data in the additionalInfo array property,
     * which can be used for storing task metadata or preferences that
     * don't fit into the standard task properties.
     *
     * @param string $key The key identifier for the information
     * @param mixed $value The value to store (can be any type that's serializable)
     * @return void
     */
    public function addAdditionalInfo(string|int $key, mixed $value): void
    {
        $this->additionalInfo[$key] = $value;
    }

    /**
     * Adds a resource to the task.
     *
     * @param TaskResource $resource The resource to add to the task
     * @return void
     */
    public function addResource(TaskResource $resource): void
    {
        $this->resources->add($resource);
    }

    /**
     * Creates a Task instance from partial data with sensible defaults.
     *
     * This helper mirrors the behavior of Project::createPartial and is useful
     * for building lightweight Task objects for UI lists or early-stage
     * construction without requiring all fields.
     *
     * @param array $data Partial task data
     * @return self
     */
    public static function createPartial(array $data): self
    {
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        $defaults = [
            'id'                        => $data['id'] ?? 0,
            'publicId'                  => $data['publicId'] ?? UUID::get(),
            'name'                      => $data['name'] ?? 'Untitled Task',
            'description'               => $data['description'] ?? null,
            'workers'                   => $data['workers'] ?? null,
            'resources'                 => $data['resources'] ?? null,
            'startDateTime'             => $data['startDateTime'] ?? new DateTime(),
            'completionDateTime'        => $data['completionDateTime'] ?? new DateTime('+7 days'),
            'actualCompletionDateTime'  => $data['actualCompletionDateTime'] ?? null,
            'priority'                  => $data['priority'] ?? TaskPriority::MEDIUM,
            'status'                    => $data['status'] ?? WorkStatus::PENDING,
            'estimatedCost'             => $data['estimatedCost'] ?? DEFAULT_RATE_MIN,
            'actualCost'                => $data['actualCost'] ?? DEFAULT_RATE_MIN,
            'budgetNote'                => $data['budgetNote'] ?? null,
            'createdAt'                 => $data['createdAt'] ?? new DateTime(),
            'additionalInfo'            => $data['additionalInfo'] ?? []
        ];

        // Handle publicId conversion (accept UUID or string)
        if (isset($data['publicId']) && !($data['publicId'] instanceof UUID)) {
            $defaults['publicId'] = UUID::tryFromString(trimOrNull($data['publicId']));
        }

        // Convert workers to WorkerContainer when provided as array
        if (isset($data['workers']) && !($data['workers'] instanceof WorkerContainer)) {
            $defaults['workers'] = is_array($data['workers'])
                ? WorkerContainer::fromArray($data['workers'])
                : new WorkerContainer();
        }

        // Date conversions
        if (isset($data['startDateTime']) && !($data['startDateTime'] instanceof DateTime)) {
            $defaults['startDateTime'] = new DateTime(trimOrNull($data['startDateTime']));
        }

        if (isset($data['completionDateTime']) && !($data['completionDateTime'] instanceof DateTime)) {
            $defaults['completionDateTime'] = new DateTime(trimOrNull($data['completionDateTime']));
        }

        if (isset($data['actualCompletionDateTime']) && !($data['actualCompletionDateTime'] instanceof DateTime)) {
            $defaults['actualCompletionDateTime'] = is_string($data['actualCompletionDateTime'])
                ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
                : $data['actualCompletionDateTime'];
        }

        if (isset($data['createdAt']) && !($data['createdAt'] instanceof DateTime)) {
            $defaults['createdAt'] = new DateTime(trimOrNull($data['createdAt']));
        }

        // Enum conversions
        if (isset($data['priority']) && !($data['priority'] instanceof TaskPriority)) {
            $defaults['priority'] = TaskPriority::tryFrom(trimOrNull($data['priority'])) ?? TaskPriority::MEDIUM;
        }

        if (isset($data['status']) && !($data['status'] instanceof WorkStatus)) {
            try {
                $defaults['status'] = WorkStatus::fromString(trimOrNull($data['status']));
            } catch (\Throwable $e) {
                $defaults['status'] = WorkStatus::PENDING;
            }
        } else {
            $defaults['status'] = WorkStatus::getStatusFromDates(
                $defaults['startDateTime'],
                $defaults['completionDateTime']
            );
        }

        return new self(
            id: $defaults['id'],
            publicId: $defaults['publicId'],
            name: $defaults['name'],
            description: $defaults['description'],
            workers: $defaults['workers'],
            resources: $defaults['resources'],  
            startDateTime: $defaults['startDateTime'],
            completionDateTime: $defaults['completionDateTime'],
            actualCompletionDateTime: $defaults['actualCompletionDateTime'],
            priority: $defaults['priority'],
            status: $defaults['status'],
            estimatedCost: $defaults['estimatedCost'],
            actualCost: $defaults['actualCost'],
            budgetNote: $defaults['budgetNote'],
            createdAt: $defaults['createdAt'],
            additionalInfo: $defaults['additionalInfo']
        );
    }

    

    /**
     * Converts the Task object to an associative array representation.
     *
     * This method transforms all task properties into a structured array format:
     * - Uses publicId for the id field
     * - Converts workers collection to array
     * - Formats all DateTime objects to ISO 8601 format (ATOM)
     * - Converts priority enum to its display name
     * - Converts status enum to its display name
     *
     * @return array Associative array containing task data with following keys:
     *      - id: string Task's public identifier
     *      - name: string Task name
     *      - description: string Task description
     *      - workers: array Collection of workers as array
     *      - resources: array Collection of resources as array
     *      - startDateTime: string Formatted task start date/time
     *      - completionDateTime: string Formatted expected completion date/time
     *      - actualCompletionDateTime: string|null Formatted actual completion date/time
     *      - priority: string Display name of the task priority
     *      - status: string Display name of the task status
     *      - estimatedCost: float Estimated cost of the task
     *      - actualCost: float Actual cost of the task
     *      - budgetNote: string|null Budget note for the task
     *      - createdAt: string Formatted creation date/time
     *      - additionalInfo: array Additional information related to the task
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $data = [
            'id'                        => UUID::toString($this->publicId),
            'name'                      => $this->name,
            'description'               => $this->description,
            'workers'                   => null,
            'resources'                 => null,
            'startDateTime'             => formatDateTime($this->startDateTime, DateTime::ATOM),
            'completionDateTime'        => formatDateTime($this->completionDateTime, DateTime::ATOM),
            'actualCompletionDateTime'  => $this->actualCompletionDateTime 
                ? formatDateTime($this->actualCompletionDateTime, DateTime::ATOM) 
                : null,
            'priority'                  => $this->priority->getDisplayName(),
            'status'                    => $this->status->getDisplayName(),
            'estimatedCost'             => $this->estimatedCost,
            'actualCost'                => $this->actualCost,
            'budgetNote'                => $this->budgetNote,
            'createdAt'                 => formatDateTime($this->createdAt, DateTime::ATOM),
            'additionalInfo'            => $this->additionalInfo
        ];

        // Include workers and resources if present
        if ($this->resources) {
            $workers = $this->resources->getWorkers();
            $data['workers'] = $workers?->toArray($useSnakeCase);

            $resources = $this->resources->getResources();
            $data['resources'] = $resources ?: null;
        }

        return $useSnakeCase ? normalizeArrayKeysToSnakeCase($data) : $data;
    }

    /**
     * Creates a Task instance from an array of data.
     *
     * This method handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Ensures workers is a WorkerContainer object
     * - Converts startDateTime string to DateTime
     * - Converts completionDateTime string to DateTime
     * - Converts actualCompletionDateTime string to DateTime
     * - Ensures priority is a TaskPriority enum
     * - Ensures status is a WorkStatus object
     * - Converts createdAt string to DateTime
     *
     * @param array $data Associative array containing task data with following keys:
     *      - id: int Task ID
     *      - publicId: string|UUID|binary Public identifier
     *      - name: string Task name
     *      - description: string Task description
     *      - workers: array|WorkerContainer Workers assigned to the task
     *      - resources: array|ResourceContainer Resources assigned to the task
     *      - startDateTime: string|DateTime Task start date and time
     *      - completionDateTime: string|DateTime Expected task completion date and time
     *      - actualCompletionDateTime: string|DateTime Actual task completion date and time
     *      - priority: string|TaskPriority Task priority level
     *      - status: string|WorkStatus Current task status
     *      - estimatedCost: float Estimated cost of the task
     *      - actualCost: float Actual cost of the task
     *      - budgetNote: string Budget note for the task
     *      - createdAt: string|DateTime Task creation timestamp
     *      - additionalInfo: array Additional information related to the task
     * 
     * @return self New Task instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        $publicId = null;
        if ($data['publicId'] instanceof UUID) {
            $publicId = $data['publicId'];
        } else if (is_string($data['publicId'])) {
            $publicId = UUID::tryFromString(trimOrNull($data['publicId']));
        }

        $workers = ($data['workers'] && !($data['workers'] instanceof WorkerContainer))
            ? WorkerContainer::fromArray($data['workers'])
            : $data['workers'];

        $resources = ($data['resources'] && !($data['resources'] instanceof ResourceContainer))
            ? ResourceContainer::fromArray($data['resources'])
            : $data['resources'];

        $startDateTime = (is_string($data['startDateTime']))
            ? new DateTime(trimOrNull($data['startDateTime']))
            : $data['startDateTime'];

        $completionDateTime = (is_string($data['completionDateTime']))
            ? new DateTime(trimOrNull($data['completionDateTime']))
            : $data['completionDateTime'];

        $actualCompletionDateTime = (is_string($data['actualCompletionDateTime']))
            ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
            : $data['actualCompletionDateTime'];

        $priority = (is_string($data['priority']))
            ? TaskPriority::tryFrom(trimOrNull($data['priority']))
            : $data['priority'];

        $status = (is_string($data['status']))
            ? WorkStatus::fromString(trimOrNull($data['status']))
            : $data['status'];

        $createdAt = (is_string($data['createdAt']))
            ? new DateTime(trimOrNull($data['createdAt']))
            : $data['createdAt'];

        return new Task(
            id: $data['id'],
            publicId: $publicId,
            name: $data['name'],
            description: $data['description'],
            workers: $workers,
            resources: $resources,
            startDateTime: $startDateTime,
            completionDateTime: $completionDateTime,
            actualCompletionDateTime: $actualCompletionDateTime,
            priority: $priority,
            status: $status,
            estimatedCost: $data['estimatedCost'] ?? DEFAULT_RATE_MIN,
            actualCost: $data['actualCost'] ?? DEFAULT_RATE_MIN,
            budgetNote: $data['budgetNote'] ?? null,
            createdAt: $createdAt,
            additionalInfo: $data['additionalInfo'] ?? []
        );
    }

    /**
     * Serializes the Task object to JSON.
     * 
     * Implements the JsonSerializable interface to control how the Task object is
     * serialized when json_encode() is called. This method delegates to the toArray()
     * method to convert the Task object into an array representation.
     * 
     * @return array Associative array containing task properties
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}