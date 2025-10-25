<?php

namespace App\Entity;

use App\Interface\Entity;
use App\Enumeration\WorkStatus;
use App\Container\TaskContainer;
use App\Container\WorkerContainer;
use App\Container\PhaseContainer;
use App\Entity\User;
use App\Core\UUID;
use App\Exception\ValidationException;
use App\Validator\UuidValidator;
use App\Validator\WorkValidator;
use BcMath\Number;
use DateTime;

class Project implements Entity
{
    private int $id;
    private UUID $publicId;
    private string $name;
    private ?string $description;
    private User $manager;
    private int $budget; // In cents to avoid floating point issues
    private ?TaskContainer $tasks;
    private WorkerContainer $workers;
    private ?PhaseContainer $phases;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;
    private DateTime $createdAt;

    protected WorkValidator $workValidator;

    /**
     * Project constructor.
     * 
     * Creates a new Project instance with the provided details.
     * All parameters are validated through WorkValidator before assignment.
     * 
     * @param int $id The unique identifier for the project in the database
     * @param UUID $publicId The public identifier for the project
     * @param string $name Project name (3-255 characters)
     * @param string|null $description Project description (5-500 characters) (optional)
     * @param User $manager The project manager (User object)
     * @param int $budget Project budget in cents (0-1,000,000, stored as cents to avoid floating point issues)
     * @param TaskContainer|null $tasks Container of tasks associated with the project (optional)
     * @param WorkerContainer $workers Container of workers assigned to the project
     * @param PhaseContainer|null $phases Container of project phases (optional)
     * @param DateTime $startDateTime Project start date and time (cannot be in the past)
     * @param DateTime $completionDateTime Expected project completion date and time (must be after start date)
     * @param DateTime|null $actualCompletionDateTime Actual completion date and time (null if not completed)
     * @param WorkStatus $status Current status of the project (enum)
     * @param DateTime $createdAt Timestamp when the project was created
     * 
     * @throws ValidationException If any of the provided data fails validation
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        string $description,
        User $manager,
        int $budget,
        ?TaskContainer $tasks,
        WorkerContainer $workers,
        ?PhaseContainer $phases,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        WorkStatus $status,
        DateTime $createdAt
    ) {
        try {
            $this->workValidator = new WorkValidator();
            $this->workValidator->validateMultiple([
                'name' => $name,
                'description' => $description,
                'budget' => $budget,
                'startDateTime' => $startDateTime,
                'completionDateTime' => $completionDateTime
            ]);
            
            if ($this->workValidator->hasErrors()) {
                throw new ValidationException("Project validation failed", $this->workValidator->getErrors());
            }
        } catch (ValidationException $th) {
            throw $th;
        }

        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = trimOrNull($name);
        $this->description = trimOrNull($description);
        $this->manager = $manager;
        $this->budget = $budget;
        $this->tasks = $tasks;
        $this->workers = $workers;
        $this->phases = $phases;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    // Getters 

    /**
     * Gets the unique identifier of the project.
     *
     * @return int The internal ID of the project
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the public identifier of the project.
     *
     * @return UUID The UUID object representing the public ID
     */
    public function getPublicId(): UUID
    {
        return $this->publicId;
    }

    /**
     * Gets the name of the project.
     *
     * @return string The project's name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the description of the project.
     *
     * @return string The project's description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Gets all tasks associated with the project.
     *
     * @return TaskContainer|null The container with the project's tasks or null if not set
     */
    public function getTasks(): ?TaskContainer
    {
        return $this->tasks;
    }

    /**
     * Gets all workers assigned to the project.
     *
     * @return WorkerContainer The container with the project's workers
     */
    public function getWorkers(): WorkerContainer
    {
        return $this->workers;
    }

    /**
     * Gets all phases of the project.
     *
     * @return PhaseContainer|null The container with the project's phases or null if not set
     */
    public function getPhases(): ?PhaseContainer
    {
        return $this->phases;
    }

    /**
     * Gets the project manager.
     *
     * @return User The User object representing the project manager
     */
    public function getManager(): User
    {
        return $this->manager;
    }

    /**
     * Gets the project budget.
     *
     * @return int The budget in cents (to avoid floating point issues)
     */
    public function getBudget(): int
    {
        return $this->budget;
    }

    /**
     * Gets the project start date and time.
     *
     * @return DateTime The DateTime object representing when the project starts
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
     * @return DateTime|null The DateTime object representing when the project was completed, or null if not completed
     */
    public function getActualCompletionDateTime(): ?DateTime
    {
        return $this->actualCompletionDateTime;
    }

    /**
     * Gets the current status of the project.
     *
     * @return WorkStatus The WorkStatus enum representing the project's status
     */
    public function getStatus(): WorkStatus
    {
        return $this->status;
    }

    /**
     * Gets the creation timestamp of the project.
     *
     * @return DateTime The DateTime object representing when the project was created
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    // Setters

    /**
     * Sets the project ID.
     *
     * @param int $id The project ID to set
     * @throws ValidationException If the ID is negative
     * @return void
     */
    public function setId(int $id): void
    {
        if ($id < 0) {
            throw new ValidationException("Invalid project ID");
        }
        $this->id = $id;
    }

    /**
     * Sets the project's public ID.
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
     * Sets the project's name.
     *
     * @param string $name The name to set (3-255 characters)
     * @throws ValidationException If the name is invalid
     * @return void
     */
    public function setName(string $name): void
    {
        $this->workValidator->validateName(trim($name));
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid project name", $this->workValidator->getErrors());
        }
        $this->name = trimOrNull($name);
    }

    /**
     * Sets the project's description.
     *
     * @param string $description The description to set (5-500 characters, optional)
     * @throws ValidationException If the description is invalid
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->workValidator->validateDescription(trim($description));
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid project description", $this->workValidator->getErrors());
        }
        $this->description = trimOrNull($description);
    }

    /**
     * Sets the project manager.
     *
     * @param User $manager The User object representing the project manager
     * @return void
     */
    public function setManager(User $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * Sets the project budget.
     *
     * @param int $budget The budget in cents (0-1,000,000, stored as cents to avoid floating point issues)
     * @throws ValidationException If the budget is invalid
     * @return void
     */
    public function setBudget(int $budget): void
    {
        $this->workValidator->validateBudget($budget);
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid project budget", $this->workValidator->getErrors());
        }
        $this->budget = $budget;
    }

    /**
     * Sets the project tasks.
     *
     * @param TaskContainer|null $tasks Container of tasks to set, or null to clear tasks
     * @return void
     */
    public function setTasks(?TaskContainer $tasks): void
    {
        $this->tasks = $tasks;
    }

    /**
     * Sets the project workers.
     *
     * @param WorkerContainer $workers Container of workers to assign to the project
     * @return void
     */
    public function setWorkers(WorkerContainer $workers): void
    {
        $this->workers = $workers;
    }

    /**
     * Sets the project phases.
     *
     * @param PhaseContainer|null $phases Container of phases to set, or null to clear phases
     * @return void
     */
    public function setPhases(?PhaseContainer $phases): void
    {
        $this->phases = $phases;
    }

    /**
     * Sets the project start date and time.
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
     * Sets the project status.
     *
     * @param WorkStatus $status The WorkStatus enum value to set
     * @return void
     */
    public function setStatus(WorkStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Sets the project creation timestamp.
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

    // OTHER METHODS (UTILITY)

    /**
     * Creates a Project instance from an array of data with partial information.
     *
     * This method provides a flexible way to create a Project instance without requiring
     * all fields to be present, supplying default values where necessary. It also
     * handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Ensures manager is a User object
     * - Ensures tasks is a TaskContainer object
     * - Ensures workers is a WorkerContainer object
     * - Ensures phases is a PhaseContainer object
     * - Converts startDateTime string to DateTime
     * - Converts completionDateTime string to DateTime
     * - Converts actualCompletionDateTime string to DateTime
     * - Ensures status is a WorkStatus enum
     * - Converts createdAt string to DateTime
     *
     * @param array $data Associative array containing project data with following possible keys:
     *      - id: int|null Project ID
     *      - publicId: string|UUID|null Public identifier
     *      - name: string Project name
     *      - description: string|null Project description
     *      - manager: array|User|null Project manager information
     *      - budget: float|int|null Project budget
     *      - tasks: array|TaskContainer|null Project tasks
     *      - workers: array|WorkerContainer|null Project workers
     *      - phases: array|PhaseContainer|null Project phases
     *      - startDateTime: string|DateTime|null Project start date and time
     *      - completionDateTime: string|DateTime|null Expected project completion date and time
     *      - actualCompletionDateTime: string|DateTime|null Actual project completion date and time
     *      - status: string|WorkStatus|null Project work status
     *      - createdAt: string|DateTime|null Project creation timestamp
     * 
     * @return self New Project instance created from provided data with defaults for missing values
     */
    public static function createPartial(array $data): self
    {
        // Provide default values for required fields
        $defaults = [
            'id' => $data['id'] ?? 0,
            'publicId' => $data['publicId'] ?? UUID::get(),
            'name' => $data['name'] ?? 'Untitled Project',
            'description' => $data['description'] ?? 'No description provided',
            'manager' => $data['manager'] ?? User::createPartial([]),
            'budget' => $data['budget'] ?? 0,
            'tasks' => $data['tasks'] ?? null,
            'workers' => $data['workers'] ?? new WorkerContainer(),
            'phases' => $data['phases'] ?? null,
            'startDateTime' => $data['startDateTime'] ?? new DateTime(),
            'completionDateTime' => $data['completionDateTime'] ?? new DateTime('+30 days'),
            'actualCompletionDateTime' => $data['actualCompletionDateTime'] ?? null,
            'status' => $data['status'] ?? WorkStatus::PENDING,
            'createdAt' => $data['createdAt'] ?? new DateTime()
        ];

        // Handle UUID conversion
        if (isset($data['publicId']) && !($data['publicId'] instanceof UUID)) {
            $defaults['publicId'] = is_string($data['publicId'])
                ? UUID::fromString(trimOrNull($data['publicId']))
                : UUID::get();
        }

        // Handle User/Manager conversion
        if (isset($data['manager']) && !($data['manager'] instanceof User)) {
            $defaults['manager'] = is_array($data['manager'])
                ? User::createPartial($data['manager'])
                : User::createPartial([]);
        }

        // Handle TaskContainer conversion
        if (isset($data['tasks']) && !($data['tasks'] instanceof TaskContainer)) {
            $defaults['tasks'] = is_array($data['tasks'])
                ? TaskContainer::fromArray($data['tasks'])
                : null;
        }

        // Handle WorkerContainer conversion
        if (isset($data['workers']) && !($data['workers'] instanceof WorkerContainer)) {
            $defaults['workers'] = is_array($data['workers'])
                ? WorkerContainer::fromArray($data['workers'])
                : new WorkerContainer();
        }

        // Handle PhaseContainer conversion
        if (isset($data['phases']) && !($data['phases'] instanceof PhaseContainer)) {
            $defaults['phases'] = is_array($data['phases'])
                ? PhaseContainer::fromArray($data['phases'])
                : null;
        }

        // Handle DateTime conversions
        if (isset($data['startDateTime']) && !($data['startDateTime'] instanceof DateTime)) {
            $defaults['startDateTime'] = new DateTime(trimOrNull($data['startDateTime']));
        }

        if (isset($data['completionDateTime']) && !($data['completionDateTime'] instanceof DateTime)) {
            $defaults['completionDateTime'] = new DateTime(trimOrNull($data['completionDateTime']));
        }

        if (isset($data['actualCompletionDateTime']) && !($data['actualCompletionDateTime'] instanceof DateTime)) {
            $defaults['actualCompletionDateTime'] = is_string($data['actualCompletionDateTime'])
                ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
                : null;
        }

        if (isset($data['createdAt']) && !($data['createdAt'] instanceof DateTime)) {
            $defaults['createdAt'] = new DateTime(trimOrNull($data['createdAt']));
        }

        // Handle enum conversions
        if (isset($data['status']) && !($data['status'] instanceof WorkStatus)) {
            $defaults['status'] = WorkStatus::from(trimOrNull($data['status']));
        }

        // Create instance with default values
        $instance = new self(
            id: $defaults['id'],
            publicId: $defaults['publicId'],
            name: $defaults['name'],
            description: $defaults['description'],
            manager: $defaults['manager'],
            budget: $defaults['budget'],
            tasks: $defaults['tasks'],
            workers: $defaults['workers'],
            phases: $defaults['phases'],
            startDateTime: $defaults['startDateTime'],
            completionDateTime: $defaults['completionDateTime'],
            actualCompletionDateTime: $defaults['actualCompletionDateTime'],
            status: $defaults['status'],
            createdAt: $defaults['createdAt']
        );

        return $instance;
    }

    /**
     * Converts the Project object to an associative array representation.
     *
     * This method transforms all project properties into a structured array format:
     * - Uses publicId for the id field
     * - Includes manager data by calling its toArray() method
     * - Converts collection objects (tasks, workers, phases) to arrays
     * - Formats all DateTime objects to ISO 8601 format (ATOM)
     * - Converts status enum to its display name
     *
     * @return array Associative array containing project data with following keys:
     *      - id: string Project's public identifier
     *      - name: string Project name
     *      - description: string Project description
     *      - manager: array Manager data as array
     *      - budget: float|int Project budget
     *      - tasks: array Collection of tasks as array
     *      - workers: array Collection of workers as array
     *      - phases: array Collection of project phases as array
     *      - startDateTime: string|null Formatted project start date/time
     *      - completionDateTime: string|null Formatted expected completion date/time
     *      - actualCompletionDateTime: string|null Formatted actual completion date/time
     *      - status: string Display name of the project status
     *      - createdAt: string Formatted creation date/time
     */
    public function toArray(): array
    {
        return [
            'id' => $this->publicId,
            'name' => $this->name,
            'description' => $this->description,
            'manager' => $this->manager->toArray(),
            'budget' => $this->budget,
            'tasks' => $this->tasks->toArray() ?? [],
            'workers' => $this->workers->toArray() ?? [],
            'phases' => $this->phases->toArray() ?? [],
            'startDateTime' => formatDateTime($this->startDateTime, DateTime::ATOM),
            'completionDateTime' => formatDateTime($this->completionDateTime, DateTime::ATOM),
            'actualCompletionDateTime' => 
                $this->actualCompletionDateTime 
                    ? formatDateTime($this->actualCompletionDateTime, DateTime::ATOM) 
                    : null,
            'status' => $this->status->getDisplayName(),
            'createdAt' => formatDateTime($this->createdAt)
        ];
    }

    /**
     * Creates a Project instance from an array of data.
     *
     * This method handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Ensures manager is a User object
     * - Ensures tasks is a TaskContainer object
     * - Ensures workers is a WorkerContainer object
     * - Ensures phases is a PhaseContainer object
     * - Converts startDateTime string to DateTime
     * - Converts completionDateTime string to DateTime
     * - Converts actualCompletionDateTime string to DateTime
     * - Ensures status is a WorkStatus enum
     * - Converts createdAt string to DateTime
     *
     * @param array $data Associative array containing project data with following keys:
     *      - id: int Project ID
     *      - publicId: string|UUID|binary Public identifier
     *      - name: string Project name
     *      - description: string Project description
     *      - manager: array|User Project manager information
     *      - budget: float|int Project budget
     *      - tasks: array|TaskContainer Project tasks
     *      - workers: array|WorkerContainer Project workers
     *      - phases: array|PhaseContainer Project phases
     *      - startDateTime: string|DateTime Project start date and time
     *      - completionDateTime: string|DateTime Expected project completion date and time
     *      - actualCompletionDateTime: string|DateTime Actual project completion date and time
     *      - status: string|WorkStatus Project work status
     *      - createdAt: string|DateTime Project creation timestamp
     * 
     * @return self New Project instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        $publicId = null;
        if ($data['publicId'] instanceof UUID) {
            $publicId = $data['publicId'];
        } else if (is_string($data['publicId'])) {
            $publicId = UUID::fromBinary(trimOrNull($data['publicId']));
        }

        $manager = (!($data['manager'] instanceof User))
            ? User::fromArray($data['manager'])
            : $data['manager'];

        $tasks = (!($data['tasks'] instanceof TaskContainer))
            ? TaskContainer::fromArray($data['tasks'])
            : $data['tasks'];

        $workers = (!($data['workers'] instanceof WorkerContainer))
            ? WorkerContainer::fromArray($data['workers'])
            : $data['workers'];

        $phases = (!($data['phases'] instanceof PhaseContainer))
            ? PhaseContainer::fromArray($data['phases'])
            : $data['phases'];

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

        $createdAt = (is_string($data['createdAt']))
            ? new DateTime(trimOrNull($data['createdAt']))
            : $data['createdAt'];

        return new Project(
            id: $data['id'],
            publicId: $publicId,
            name: trimOrNull($data['name']),
            description: trimOrNull($data['description']),
            manager: User::fromArray($data['manager']),
            budget: $data['budget'],
            tasks: $tasks,
            workers: $workers,
            phases: $phases,
            startDateTime: $startDateTime,
            completionDateTime: $completionDateTime,
            actualCompletionDateTime: $actualCompletionDateTime,
            status: $status,
            createdAt: $createdAt
        );
    }

    /**
     * Returns a serialized representation of the project as an array.
     * 
     * This method implements the JsonSerializable interface and allows the project
     * object to be serialized to JSON when using json_encode().
     * 
     * @return array The project data as an associative array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
