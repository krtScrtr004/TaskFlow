<?php

namespace App\Entity;

use App\Interface\Entity;
use App\Enumeration\WorkStatus;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Entity\Task;
use App\Exception\ValidationException;
use App\Validator\WorkValidator;
use DateTime;

class Phase implements Entity
{
    private int $id;
    private UUID $publicId;
    private string $name;
    private ?string $description;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private DateTime|null $actualCompletionDateTime;
    private WorkStatus $status;
    private TaskContainer|null $tasks;
    private float $budget;
    private float $contingencyRate;
    private string|null $budgetNote;
    private DateTime|null $createdAt;


    protected WorkValidator $workValidator;

    /**
     * Phase constructor.
     *
     * @param int $id The internal ID of the phase
     * @param UUID $publicId The public UUID of the phase
     * @param string $name The name of the phase
     * @param DateTime $startDateTime The start date and time of the phase
     * @param DateTime $completionDateTime The expected completion date and time of the phase
     * @param WorkStatus $status The current status of the phase
     * 
     * OPTIONAL / WITH DEFAULT VALUES:
     * @param string|null $description The description of the phase (optional)
     * @param float $budget The budget allocated for the phase (default: BUDGET_MIN)
     * @param float $contingencyRate The contingency rate for the phase budget (default: CONTINGENCY_RATE_MIN)
     * @param DateTime|null $actualCompletionDateTime The actual completion date and time of the phase (optional)
     * @param TaskContainer|null $tasks Container of tasks assigned to the phase (optional)
     * @param string|null $budgetNote Notes regarding the phase budget (optional)
     * @param DateTime|null $createdAt The creation timestamp of the phase (optional)
     * 
     * @throws ValidationException If any validation rules are violated
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        WorkStatus $status,

        ?string $description,
        float $budget = BUDGET_MIN,
        float $contingencyRate = CONTINGENCY_RATE_MIN,
        DateTime|null $actualCompletionDateTime = null,
        TaskContainer|null $tasks = null,
        string|null $budgetNote = null,
        DateTime|null $createdAt = null
    ) {
        try {
            $this->workValidator = new WorkValidator();
            $this->workValidator->validateMultiple([
                'name'                  => $name,
                'description'           => $description,
                'startDateTime'         => $startDateTime,
                'completionDateTime'    => $completionDateTime,
                'budget'                => $budget,
                'contingencyRate'       => $contingencyRate,
                'budgetNote'            => $budgetNote
            ]);

            if ($this->workValidator->hasErrors())
                throw new ValidationException(
                    "Phase Validation Failed",
                    $this->workValidator->getErrors()
                );
        } catch (ValidationException $th) {
            throw $th;
        }

        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = trimOrNull($name);
        $this->description = trimOrNull($description);
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
        $this->tasks = $tasks;
        $this->budget = $budget;
        $this->contingencyRate = $contingencyRate;
        $this->budgetNote = trimOrNull($budgetNote);
        $this->createdAt = $createdAt;
    }

    // Getters

    /**
     * Gets the unique identifier of the phase.
     *
     * @return int The internal ID of the phase
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the public identifier of the phase.
     *
     * @return UUID The UUID object representing the public ID
     */
    public function getPublicId(): UUID
    {
        return $this->publicId;
    }

    /**
     * Gets the name of the phase.
     *
     * @return string The phase's name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the description of the phase.
     *
     * @return string The phase's description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Gets the phase start date and time.
     *
     * @return DateTime The DateTime object representing when the phase starts
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

    public function getActualCompletionDateTime(): DateTime|null
    {
        return $this->actualCompletionDateTime;
    }

    /**
     * Gets the current status of the phase.
     *
     * @return WorkStatus The WorkStatus enum representing the phase's status
     */
    public function getStatus(): WorkStatus
    {
        return $this->status;
    }

    /**
     * Gets all tasks assigned to the phase.
     *
     * @return TaskContainer|null The container with the phase's tasks, or null if not loaded
     */
    public function getTasks(): TaskContainer|null
    {
        return $this->tasks;
    }

    /**
     * Gets the budget allocated for the phase.
     *
     * @return float The budget amount for the phase
     */
    public function getBudget(): float
    {
        return $this->budget;
    }

    /**
     * Gets the contingency rate for the phase budget.
     *
     * @return float The contingency rate percentage
     */
    public function getContingencyRate(): float
    {
        return $this->contingencyRate;
    }

    /**
     * Gets the budget notes for the phase.
     *
     * @return string|null The notes regarding the phase budget
     */
    public function getBudgetNote(): string|null
    {
        return $this->budgetNote;
    }

    /**
     * Gets the creation timestamp of the phase.
     *
     * @return DateTime|null The creation timestamp or null if not set
     */
    public function getCreatedAt(): DateTime|null
    {
        return $this->createdAt;
    }

    // Setters

    /**
     * Sets the phase ID.
     *
     * @param int $id The phase ID to set
     * @throws ValidationException If the ID is negative
     * @return void
     */
    public function setId(int $id): void
    {
        if ($id < 0) throw new ValidationException("Invalid ID");
        $this->id = $id;
    }

    /**
     * Sets the phase's public ID.
     *
     * @param UUID $publicId The UUID to set as public ID
     * @return void
     */
    public function setPublicId(UUID $publicId): void
    {
        $this->publicId = $publicId;
    }

    /**
     * Sets the phase's name.
     *
     * @param string $name The name to set (3-255 characters)
     * @throws ValidationException If the name is invalid
     * @return void
     */
    public function setName(string $name): void
    {
        $this->workValidator->validateName(trim($name));
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                "Invalid Phase Name",
                $this->workValidator->getErrors()
            );
        $this->name = trimOrNull($name);
    }

    /**
     * Sets the phase's description.
     *
     * @param string|null $description The description to set, or null to unset
     * @throws ValidationException If the description is invalid
     * @return void
     */
    public function setDescription(string|null $description): void
    {
        $tempDescription = $description ? trimOrNull($description) : null;
        if ($tempDescription) {
            $this->workValidator->validateDescription(trim($tempDescription));
            if ($this->workValidator->hasErrors())
                throw new ValidationException(
                    'Invalid Description',
                    $this->workValidator->getErrors()
                );
        }

        $this->description = $tempDescription;
    }

    /**
     * Sets the phase start date and time.
     *
     * @param DateTime $startDateTime The start date and time to set (cannot be in the past)
     * @throws ValidationException If the start date is invalid or in the past
     * @return void
     */
    public function setStartDateTime(DateTime $startDateTime): void
    {
        $this->workValidator->validateStartDateTime($startDateTime);
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                'Invalid Start Date',
                $this->workValidator->getErrors()
            );
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
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                'Invalid Completion Date',
                $this->workValidator->getErrors()
            );
        $this->completionDateTime = $completionDateTime;
    }

    /**
     * Sets the actual completion date and time.
     *
     * @param DateTime|null $actualCompletionDateTime The actual completion date and time to set, or null to unset
     * @return void
     */
    public function setActualCompletionDateTime(DateTime|null $actualCompletionDateTime): void
    {
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    /**
     * Sets the phase status.
     *
     * @param WorkStatus $status The WorkStatus enum value to set
     * @return void
     */
    public function setStatus(WorkStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Sets the phase's tasks container.
     *
     * @param TaskContainer|null $tasks Container of tasks to assign to this phase, or null to unset
     * @return void
     */
    public function setTasks(TaskContainer|null $tasks): void
    {
        $this->tasks = $tasks;
    }

    /**
     * Sets the budget allocated for the phase.
     *
     * @param float $budget The budget amount to set
     * @throws ValidationException If the budget amount is invalid
     * @return void
     */
    public function setBudget(float $budget): void
    {
        $this->workValidator->validateBudget($budget);
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                'Invalid Budget',
                $this->workValidator->getErrors()
            );
        $this->budget = $budget;
    }

    /**
     * Sets the contingency rate for the phase budget.
     *
     * @param float $contingencyRate The contingency rate percentage to set
     * @throws ValidationException If the contingency rate is invalid
     * @return void
     */
    public function setContingencyRate(float $contingencyRate): void
    {
        $this->workValidator->validateContingencyRate($contingencyRate);
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                'Invalid Contingency Rate',
                $this->workValidator->getErrors()
            );
        $this->contingencyRate = $contingencyRate;
    }

    /**
     * Sets the budget notes for the phase.
     *
     * @param string|null $budgetNote The notes regarding the phase budget, or null to unset
     * @throws ValidationException If the budget note is invalid
     * @return void
     */
    public function setBudgetNote(string|null $budgetNote): void
    {
        $tempBudgetNote = $budgetNote ? trimOrNull($budgetNote) : null;
        if ($tempBudgetNote) {
            $this->workValidator->validateBudgetNote(trimOrNull($tempBudgetNote));
            if ($this->workValidator->hasErrors())
                throw new ValidationException(
                    'Invalid Budget Note', 
                    $this->workValidator->getErrors()
                );
        }
        $this->budgetNote = trimOrNull($budgetNote);
    }

    /**
     * Adds a task to the phase's task collection.
     *
     * @param Task $task The task instance to be added to this phase
     * @return void
     */
    public function addTask(Task $task): void
    {
        if (!$this->tasks) $this->tasks = new TaskContainer();
        $this->tasks->add($task);
    }

    /**
     * Creates a Phase instance from an array of data with partial information.
     *
     * This method provides a flexible way to create a Phase instance without requiring
     * all fields to be present, supplying default values where necessary. It also
     * handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Converts startDateTime string to DateTime
     * - Converts completionDateTime string to DateTime
     * - Converts actualCompletionDateTime string to DateTime
     * - Ensures status is a WorkStatus enum
     *
     * @param array $data Associative array containing phase data with following possible keys:
     *      - id: int|null Phase ID
     *      - publicId: string|UUID|null Public identifier
     *      - name: string Phase name
     *      - description: string|null Phase description
     *      - startDateTime: string|DateTime|null Phase start date and time
     *      - completionDateTime: string|DateTime|null Expected completion date and time
     *      - actualCompletionDateTime: string|DateTime|null Actual completion date and time
     *      - status: string|WorkStatus|null Current work status of the phase
     *      - tasks: TaskContainer|null Container of tasks associated with the phase
     *      - budget: float|int|null Budget amount allocated for the phase
     *      - contingencyRate: float|int|null Contingency rate percentage for the phase budget
     *      - budgetNote: string|null Notes regarding the phase budget
     * 
     * @return self New Phase instance created from provided data with defaults for missing values
     */
    public static function createPartial(array $data): self
    {
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        // Provide default values for required fields
        $defaults = [
            'id'                        => $data['id'] ?? mt_rand(),
            'publicId'                  => $data['publicId'] ?? UUID::get(),
            'name'                      => $data['name'] ?? 'Untitled Phase',
            'description'               => $data['description'] ?? null,
            'startDateTime'             => $data['startDateTime'] ?? new DateTime(),
            'completionDateTime'        => $data['completionDateTime'] ?? new DateTime('+7 days'),
            'actualCompletionDateTime'  => $data['actualCompletionDateTime'] ?? null,
            'status'                    => $data['status'] ?? WorkStatus::PENDING,
            'tasks'                     => $data['tasks'] ?? null,
            'budget'                    => $data['budget'] ?? BUDGET_MIN,
            'contingencyRate'           => $data['contingencyRate'] ?? CONTINGENCY_RATE_MIN,
            'budgetNote'                => $data['budgetNote'] ?? '',
            'createdAt'                 => $data['createdAt'] ?? null
        ];

        // Handle UUID conversion
        if (isset($data['publicId']) && !$data['publicId'] instanceof UUID)
            $defaults['publicId'] = UUID::tryFromString(trimOrNull($data['publicId']));

        if (isset($data['status']) && !$data['status'] instanceof WorkStatus)
            $defaults['status'] = WorkStatus::from(trimOrNull($data['status']));

        if (isset($data['budget']) && !\is_float($data['budget']))
            $defaults['budget'] = (float) $data['budget'];

        if (isset($data['contingencyRate']) && !\is_float($data['contingencyRate']))
            $defaults['contingencyRate'] = (float) $data['contingencyRate'];

        if (isset($data['budgetNote']))
            $defaults['budgetNote'] = trimOrNull($data['budgetNote']);

        if (isset($data['tasks']) && !$data['tasks'] instanceof TaskContainer)
            $defaults['tasks'] = TaskContainer::fromArray($data['tasks']);

        if (isset($data['startDateTime']) && !$data['startDateTime'] instanceof DateTime)
            $defaults['startDateTime'] = new DateTime(trimOrNull($data['startDateTime']));

        if (isset($data['completionDateTime']) && !$data['completionDateTime'] instanceof DateTime)
            $defaults['completionDateTime'] = new DateTime(trimOrNull($data['completionDateTime']));

        if (isset($data['actualCompletionDateTime']) && !$data['actualCompletionDateTime'] instanceof DateTime) {
            $defaults['actualCompletionDateTime'] = is_string($data['actualCompletionDateTime'])
                ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
                : null;
        }

        if (isset($data['createdAt']) && !$data['createdAt'] instanceof DateTime) {
            $defaults['createdAt'] = is_string($data['createdAt'])
                ? new DateTime(trimOrNull($data['createdAt']))
                : null;
        }

        // Create instance with default values
        $instance = new self(
            id: $defaults['id'],
            publicId: $defaults['publicId'],
            name: $defaults['name'],
            description: $defaults['description'],
            startDateTime: $defaults['startDateTime'],
            completionDateTime: $defaults['completionDateTime'],
            actualCompletionDateTime: $defaults['actualCompletionDateTime'],
            status: $defaults['status'],
            tasks: $defaults['tasks'],
            budget: $defaults['budget'],
            contingencyRate: $defaults['contingencyRate'],
            budgetNote: $defaults['budgetNote'],
            createdAt: $defaults['createdAt']
        );

        return $instance;
    }

    /**
     * Converts the Phase object to an associative array representation.
     *
     * This method transforms all phase properties into a structured array format:
     * - Uses publicId for the id field (falls back to uniqid if not set)
     * - Formats all DateTime objects to ISO 8601 format (ATOM)
     * - Converts status enum to its string value
     *
     * @return array Associative array containing phase data with following keys:
     *      - id: string Phase's public identifier
     *      - name: string Phase name
     *      - description: string Phase description
     *      - startDateTime: string Formatted phase start date/time
     *      - completionDateTime: string Formatted expected completion date/time
     *      - actualCompletionDateTime: string|null Formatted actual completion date/time
     *      - status: string String value of the phase status
     *      - tasks: array Array representation of the phase's tasks
     *      - budget: float Budget amount allocated for the phase
     *      - contingencyRate: float Contingency rate percentage for the phase budget
     *      - budgetNote: string Notes regarding the phase budget
     *      - createdAt: string|null Formatted creation timestamp
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $data = [
            'id'                        => UUID::toString($this->publicId),
            'name'                      => $this->name,
            'description'               => $this->description,
            'startDateTime'             => formatDateTime($this->startDateTime),
            'completionDateTime'        => formatDateTime($this->completionDateTime),
            'actualCompletionDateTime'  => $this->actualCompletionDateTime
                ? formatDateTime($this->actualCompletionDateTime)
                : null,
            'status'                    => $this->status->value,
            'tasks'                     => $this->tasks?->toArray($useSnakeCase) ?? [],
            'budget'                    => $this->budget,
            'contingencyRate'           => $this->contingencyRate,
            'budgetNote'                => $this->budgetNote,
            'createdAt'                 => $this->createdAt
                ? formatDateTime($this->createdAt)
                : null
        ];

        return $useSnakeCase ? normalizeArrayKeysToSnakeCase($data) : $data;
    }

    /**
     * Creates a Phase instance from an array of data.
     *
     * This method handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Converts startDateTime string to DateTime
     * - Converts completionDateTime string to DateTime
     * - Converts actualCompletionDateTime string to DateTime
     * - Ensures status is a WorkStatus enum
     *
     * @param array $data Associative array containing phase data with following keys:
     *      - id: int Phase ID
     *      - publicId: string|UUID|binary Public identifier
     *      - name: string Phase name
     *      - description: string Phase description
     *      - startDateTime: string|DateTime Phase start date and time
     *      - completionDateTime: string|DateTime Expected completion date and time
     *      - actualCompletionDateTime: string|DateTime Actual completion date and time
     *      - status: string|WorkStatus Current work status of the phase
     *      - tasks: array|TaskContainer Tasks associated with the phase
     *      - budget: float|int Budget amount allocated for the phase
     *      - contingencyRate: float|int Contingency rate percentage for the phase budget
     *      - budgetNote: string Notes regarding the phase budget
     *      - createdAt: string|null Formatted creation timestamp
     * 
     * @return self New Phase instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        $publicId = null;
        if ($data['publicId'] instanceof UUID)
            $publicId = $data['publicId'];
        elseif (is_string($data['publicId'])) 
            $publicId = UUID::tryFromString(trimOrNull($data['publicId']));

        $startDateTime = (is_string($data['startDateTime']))
            ? new DateTime(trimOrNull($data['startDateTime']))
            : $data['startDateTime'];

        $completionDateTime = (is_string($data['completionDateTime']))
            ? new DateTime(trimOrNull($data['completionDateTime']))
            : $data['completionDateTime'];

        $actualCompletionDateTime = (is_string($data['actualCompletionDateTime']))
            ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
            : $data['actualCompletionDateTime'];

        $status = (is_string($data['status']))
            ? WorkStatus::fromString(trimOrNull($data['status']))
            : $data['status'];

        $tasks = (!($data['tasks'] instanceof TaskContainer))
            ? TaskContainer::fromArray($data['tasks'])
            : $data['tasks'];

        $createdAt = (is_string($data['createdAt']))
            ? new DateTime(trimOrNull($data['createdAt']))
            : $data['createdAt'];

        return new self(
            id: $data['id'],
            publicId: $publicId,
            name: trimOrNull($data['name']),
            description: trimOrNull($data['description'] ?? ''),
            startDateTime: $startDateTime,
            completionDateTime: $completionDateTime,
            actualCompletionDateTime: $actualCompletionDateTime,
            status: $status,
            tasks: $tasks,
            budget: (float) $data['budget'],
            contingencyRate: (float) $data['contingencyRate'],
            budgetNote: trimOrNull($data['budgetNote'] ?? ''),
            createdAt: $createdAt
        );
    }

    /**
     * Serializes the Phase object to JSON.
     * 
     * Implements the JsonSerializable interface by converting the Phase object
     * to an array representation through the toArray method.
     *
     * @return array Associative array containing the Phase's data
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
