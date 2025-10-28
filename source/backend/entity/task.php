<?php

namespace App\Entity;

use App\Interface\Entity;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Container\WorkerContainer;
use App\Dependent\Worker;
use App\Core\UUID;
use App\Exception\ValidationException;
use App\Model\TaskModel;
use App\Validator\UuidValidator;
use App\Validator\WorkValidator;
use DateTime;

class Task implements Entity
{
    private int $id;
    private UUID $publicId;
    private string $name;
    private ?string $description;
    private WorkerContainer $workers;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private TaskPriority $priority;
    private WorkStatus $status;
    private DateTime $createdAt;

    protected WorkValidator $workValidator;

    /**
     * Task constructor.
     * 
     * Creates a new Task instance with the provided details.
     * All parameters are validated through WorkValidator before assignment.
     * 
     * @param int $id The unique identifier for the task in the database
     * @param UUID $publicId The public identifier for the task
     * @param string $name Task name (3-255 characters)
     * @param string|null $description Task description (5-500 characters) (optional)
     * @param WorkerContainer $workers Container of workers assigned to the task
     * @param DateTime $startDateTime Task start date and time (cannot be in the past)
     * @param DateTime $completionDateTime Expected task completion date and time (must be after start date)
     * @param DateTime|null $actualCompletionDateTime Actual completion date and time (null if not completed)
     * @param TaskPriority $priority Task priority level (enum)
     * @param WorkStatus $status Current status of the task (enum)
     * @param DateTime $createdAt Timestamp when the task was created
     * 
     * @throws ValidationException If any of the provided data fails validation
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        ?string $description,
        WorkerContainer $workers,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        TaskPriority $priority,
        WorkStatus $status,
        DateTime $createdAt
    ) {
        try {
            $this->workValidator = new WorkValidator();
            $this->workValidator->validateMultiple([
                'name' => $name,
                'description' => $description,
                'startDateTime' => $startDateTime,
                'completionDateTime' => $completionDateTime
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
        $this->workers = $workers;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->priority = $priority;
        $this->status = $status;
        $this->createdAt = $createdAt;
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
     * @return string The task's description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Gets all workers assigned to the task.
     *
     * @return WorkerContainer The container with the task's workers
     */
    public function getWorkers(): WorkerContainer
    {
        return $this->workers;
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
     * Gets the creation timestamp of the task.
     *
     * @return DateTime The DateTime object representing when the task was created
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
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
        $this->workers = $workers;
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

    // Other methods (Utility)

    /**
     * Adds a worker to the task.
     *
     * @param Worker $worker The worker to add to the task
     * @return void
     */
    public function addWorker(Worker $worker): void
    {
        $this->workers->add($worker);
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
     *      - startDateTime: string Formatted task start date/time
     *      - completionDateTime: string Formatted expected completion date/time
     *      - actualCompletionDateTime: string|null Formatted actual completion date/time
     *      - priority: string Display name of the task priority
     *      - status: string Display name of the task status
     *      - createdAt: string Formatted creation date/time
     */
    public function toArray(): array
    {
        return [
            'id' => UUID::toString($this->publicId),
            'name' => $this->name,
            'description' => $this->description,
            'workers' => $this->workers->toArray(),
            'startDateTime' => formatDateTime($this->startDateTime, DateTime::ATOM),
            'completionDateTime' => formatDateTime($this->completionDateTime, DateTime::ATOM),
            'actualCompletionDateTime' => 
                $this->actualCompletionDateTime 
                    ? formatDateTime($this->actualCompletionDateTime, DateTime::ATOM) 
                    : null,
            'priority' => $this->priority->getDisplayName(),
            'status' => $this->status->getDisplayName(),
            'createdAt' => formatDateTime($this->createdAt, DateTime::ATOM)
        ];
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
     *      - startDateTime: string|DateTime Task start date and time
     *      - completionDateTime: string|DateTime Expected task completion date and time
     *      - actualCompletionDateTime: string|DateTime Actual task completion date and time
     *      - priority: string|TaskPriority Task priority level
     *      - status: string|WorkStatus Current task status
     *      - createdAt: string|DateTime Task creation timestamp
     * 
     * @return self New Task instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        $publicId = null;
        if ($data['publicId'] instanceof UUID) {
            $publicId = $data['publicId'];
        } else if (is_string($data['publicId'])) {
            $publicId = UUID::fromBinary(trimOrNull($data['publicId']));
        }

        $workers = (!($data['workers'] instanceof WorkerContainer))
            ? WorkerContainer::fromArray($data['workers'])
            : $data['workers'];

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
            startDateTime: $startDateTime,
            completionDateTime: $completionDateTime,
            actualCompletionDateTime: $actualCompletionDateTime,
            priority: $priority,
            status: $status,
            createdAt: $createdAt
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