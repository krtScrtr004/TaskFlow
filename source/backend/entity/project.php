<?php

namespace App\Entity;

use App\Entity\Phase;
use App\Interface\Entity;
use App\Entity\Worker;
use App\Enumeration\WorkStatus;
use App\Container\TaskContainer;
use App\Container\WorkerContainer;
use App\Container\PhaseContainer;
use App\Core\UUID;
use App\Entity\ProjectManager;
use App\Exception\ValidationException;
use App\Validator\UuidValidator;
use App\Validator\WorkValidator;
use DateTime;

class Project implements Entity
{
    private int $id;
    private UUID $publicId;
    private string $name;
    private ?string $description;
    private ProjectManager $manager;
    private float $budget;
    private int $maxWorkers;
    private ?TaskContainer $tasks;
    private ?WorkerContainer $workers;
    private ?PhaseContainer $phases;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;
    private ?DateTime $createdAt;
    private array $additionalInfo;

    protected WorkValidator $workValidator;

    /**
     * Constructs a Project entity.
     *
     * This constructor validates core project fields, initializes an internal WorkValidator,
     * normalizes certain inputs (e.g. trimming name/description), and assigns all provided
     * parameters to the instance properties.
     *
     * Behavior and side effects:
     * - Instantiates a WorkValidator and calls validateMultiple() with the fields:
     *   'name', 'description', 'budget', 'maxWorkers', 'startDateTime', 'completionDateTime'.
     * - If validation errors are present, a ValidationException is thrown containing the validator's errors.
     * - Normalizes textual fields using trimOrNull() for $name and $description.
     * - Assigns provided container arguments ($tasks, $workers, $phases) and date/time arguments
     *   directly to the instance without performing persistence, cloning, or deep validation of
     *   the container contents.
     * - Does not perform side effects such as database operations, external I/O, or automatic
     *   lifecycle transitions for tasks/workers; it merely sets internal properties.
     *
     * @param int $id Internal numeric identifier for the project.
     * @param UUID $publicId Publicly visible unique identifier for the project.
     * @param string $name Human-readable project name (will be trimmed; cannot be empty per validation).
     * @param ProjectManager $manager Manager responsible for the project.
     * @param DateTime $startDateTime Scheduled start date/time of the project.
     * @param DateTime $completionDateTime Scheduled completion date/time of the project.
     * @param WorkStatus $status Current work status of the project.
     *
     * // Optional parameters
     * @param string|null $description Optional detailed description (trimmed or set to null).
     * @param float $budget Project budget; defaults to BUDGET_MIN if not provided.
     * @param int $maxWorkers Maximum allowed workers for the project; defaults to WORKER_COUNT_MIN.
     * @param TaskContainer|null $tasks Optional container of Task instances associated with the project.
     * @param WorkerContainer|null $workers Optional container of Worker instances associated with the project.
     * @param PhaseContainer|null $phases Optional container of Phase instances associated with the project.
     * @param DateTime|null $actualCompletionDateTime Actual completion timestamp, if the project has finished.
     * @param DateTime|null $createdAt Creation timestamp for the project entity (may be null).
     * @param array $additionalInfo Arbitrary associative array for extra metadata about the project.
     *
     * @throws ValidationException If validation via WorkValidator fails (contains validation error details).
     *
     * @return void
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        ProjectManager $manager,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        WorkStatus $status,

        // Optional
        string|null $description = null,
        float $budget = BUDGET_MIN,
        int $maxWorkers = WORKER_COUNT_MIN,
        TaskContainer|null $tasks = null,
        WorkerContainer|null $workers = null,
        PhaseContainer|null $phases = null,
        DateTime|null $actualCompletionDateTime = null,
        DateTime|null $createdAt = null,
        array $additionalInfo = []
    ) {
        try {
            $this->workValidator = new WorkValidator();
            $this->workValidator->validateMultiple([
                'name'                  => $name,
                'description'           => $description,
                'budget'                => $budget,
                'maxWorkers'            => $maxWorkers,
                'startDateTime'         => $startDateTime,
                'completionDateTime'    => $completionDateTime
            ]);

            if ($this->workValidator->hasErrors())
                throw new ValidationException(
                    'Project Validation Failed',
                    $this->workValidator->getErrors()
                );
        } catch (ValidationException $th) {
            throw $th;
        }

        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = trimOrNull($name);
        $this->description = trimOrNull($description);
        $this->manager = $manager;
        $this->budget = $budget;
        $this->maxWorkers = $maxWorkers;
        $this->tasks = $tasks;
        $this->workers = $workers;
        $this->phases = $phases;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->additionalInfo = $additionalInfo;
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
    public function getTasks(): TaskContainer|null
    {
        return $this->tasks;
    }

    /**
     * Gets all workers assigned to the project.
     *
     * @return WorkerContainer|null The container with the project's workers or null if not set
     */
    public function getWorkers(): WorkerContainer|null
    {
        return $this->workers;
    }

    /**
     * Gets all phases of the project.
     *
     * @return PhaseContainer|null The container with the project's phases or null if not set
     */
    public function getPhases(): PhaseContainer|null
    {
        return $this->phases;
    }

    /**
     * Gets the project manager.
     *
     * @return ProjectManager The ProjectManager object representing the project manager
     */
    public function getManager(): ProjectManager
    {
        return $this->manager;
    }

    /**
     * Gets the maximum number of workers allowed for the project.
     *
     * @return int The maximum number of workers allowed for the project
     */
    public function getMaxWorkers(): int
    {
        return $this->maxWorkers;
    }

    /**
     * Gets the project budget.
     *
     * @return int The budget in cents (to avoid floating point issues)
     */
    public function getBudget(): float
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
    public function getActualCompletionDateTime(): DateTime|null
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

    /**
     * Retrieves a value from the additional information array by key.
     *
     * This method provides safe access to the additionalInfo array, returning null
     * if the requested key does not exist instead of throwing an error.
     *
     * @param string $key The key to look up in the additional information array
     * 
     * @return mixed The value associated with the key if it exists, null otherwise
     */
    public function getAdditionalInfo(string $key): mixed
    {
        return $this->additionalInfo[$key] ?? null;
    }

    /**
     * Retrieves all additional information associated with the project.
     *
     * This method returns the complete array of additional information stored
     * for the project. The additional information can contain any supplementary
     * data that doesn't fit into the standard project properties.
     *
     * @return array Associative array containing all additional project information.
     *               Returns an empty array if no additional information is set.
     */
    public function getAllAdditionalInfo(): array
    {
        return $this->additionalInfo;
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
        if ($id < 0) throw new ValidationException('Invalid ID');
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
        if ($uuidValidator->hasErrors())
            throw new ValidationException(
                'Invalid Public ID',
                $uuidValidator->getErrors()
            );
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
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                'Invalid Name',
                $this->workValidator->getErrors()
            );
        $this->name = trimOrNull($name);
    }

    /**
     * Sets the project's description.
     *
     * @param string|null $description The description to set, or null to unset
     * @throws ValidationException If the description is invalid
     * @return void
     */
    public function setDescription(string|null $description): void
    {
        $tempDescription = $description ? trimOrNull($description) : null;
        if ($tempDescription) {
            $this->workValidator->validateDescription($tempDescription);
            if ($this->workValidator->hasErrors())
                throw new ValidationException(
                'Invalid Description', 
                $this->workValidator->getErrors()
            );
        }
        $this->description = $tempDescription;
    }

    /**
     * Sets the project manager.
     *
     * @param ProjectManager $manager The ProjectManager object representing the project manager
     * @return void
     */
    public function setManager(ProjectManager $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * Sets the maximum number of workers allowed for the project.
     *
     * @param int $maxWorkers The maximum number of workers (1-100)
     * @throws ValidationException If the max workers is invalid
     * @return void
     */
    public function setMaxWorkers(int $maxWorkers): void
    {
        $this->workValidator->validateMaxWorkers($maxWorkers);
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                'Invalid Max Workers', 
                $this->workValidator->getErrors()
            );
        $this->maxWorkers = $maxWorkers;
    }

    /**
     * Sets the project budget.
     *
     * @param int $budget The budget in cents (0-1,000,000, stored as cents to avoid floating point issues)
     * @throws ValidationException If the budget is invalid
     * @return void
     */
    public function setBudget(float $budget): void
    {
        $this->workValidator->validateBudget($budget);
        if ($this->workValidator->hasErrors())
            throw new ValidationException(
                "Invalid Budget", 
                $this->workValidator->getErrors()
            );
        $this->budget = $budget;
    }

    /**
     * Sets the project tasks.
     *
     * @param TaskContainer|null $tasks Container of tasks to set, or null to clear tasks
     * @return void
     */
    public function setTasks(TaskContainer|null $tasks): void
    {
        $this->tasks = $tasks;
    }

    /**
     * Sets the project workers.
     *
     * @param WorkerContainer|null $workers Container of workers to assign to the project, or null to clear workers
     * @return void
     */
    public function setWorkers(WorkerContainer|null $workers): void
    {
        $this->workers = $workers;
    }

    /**
     * Sets the project phases.
     *
     * @param PhaseContainer|null $phases Container of phases to set, or null to clear phases
     * @return void
     */
    public function setPhases(PhaseContainer|null $phases): void
    {
        $this->phases = $phases;
    }

    /**
     * Sets the project start date and time.
     *
     * @param DateTime|null $startDateTime The start date and time to set (cannot be in the past)
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
     * @param DateTime|null $actualCompletionDateTime The actual completion date and time, or null if not completed
     * @return void
     */
    public function setActualCompletionDateTime(DateTime|null $actualCompletionDateTime): void
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
     * @param DateTime|null $createdAt The creation timestamp to set, or null to unset
     * @throws ValidationException If the creation date is in the future
     * @return void
     */
    public function setCreatedAt(DateTime|null $createdAt): void
    {
        if ($createdAt && $createdAt > new DateTime()) throw new ValidationException('Invalid Creation Date');
        $this->createdAt = $createdAt;
    }

    // OTHER METHODS (UTILITY)

    /**
     * Checks if the specified key exists in the additionalInfo array.
     *
     * This method determines whether the given key is present in the project's additional information.
     * The key can be either an integer or a string, and the method returns true if the key exists,
     * otherwise false.
     *
     * @param int|string $key The key to check for existence in the additionalInfo array.
     * 
     * @return bool True if the key exists in additionalInfo, false otherwise.
     */
    public function additionalInfoContains(int|string $key): bool
    {
        return \array_key_exists($key, $this->additionalInfo);
    }

    /**
     * Adds or updates additional information for the project.
     *
     * This method allows storing custom key-value pairs in the project's
     * additional information array. If the key already exists, its value
     * will be updated. This is useful for storing metadata or custom
     * properties that don't fit into the standard project structure.
     *
     * @param string $key The key identifier for the additional information
     * @param mixed $value The value to store (can be any type)
     * 
     * @return void
     */
    public function addAdditionalInfo(int|string $key, mixed $value): void
    {
        $this->additionalInfo[$key] = $value;
    }

    /**
     * Adds a phase to the project's phase collection.
     *
     * This method adds a new Phase instance to the project's phases collection.
     * The phase is appended to the existing collection of phases associated with
     * this project.
     *
     * @param Phase $phase The Phase instance to be added to the project's collection
     * 
     * @return void
     */
    public function addPhase(Phase $phase): void
    {
        if (!$this->phases) $this->phases = new PhaseContainer();
        $this->phases->add($phase);
    }

    /**
     * Adds a task to the project's task collection.
     *
     * This method associates a task with the current project by adding it to the
     * project's tasks collection. The task is added to the internal Doctrine collection
     * which maintains the relationship between the project and its tasks.
     *
     * @param Task $task The task instance to be added to this project
     * 
     * @return void
     */
    public function addTask(Task $task): void
    {
        if (!$this->tasks) $this->tasks = new TaskContainer();
        $this->tasks->add($task);
    }

    /**
     * Adds a worker to the project's worker collection.
     *
     * This method associates a worker with the current project by adding them
     * to the internal workers collection. The worker is added to the collection
     * without checking for duplicates, as collection management is handled by
     * the underlying collection implementation.
     *
     * @param Worker $worker The worker instance to be added to the project
     * 
     * @return void
     */
    public function addWorker(Worker $worker): void
    {
        if (!$this->workers) $this->workers = new WorkerContainer();
        $this->workers->add($worker);
    }

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
     *      - manager: array|ProjectManager|null Project manager information
     *      - maxWorkers: int|null Maximum number of workers
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
        // Normalize input keys to camelCase to support both snake_case and camelCase input
        $data = normalizeArrayKeysToCamelCase($data);

        // Provide default values for required fields
        $defaults = [
            'id'                            => $data['id'] ?? 0,
            'publicId'                      => $data['publicId'] ?? UUID::get(),
            'name'                          => $data['name'] ?? 'Untitled Project',
            'description'                   => $data['description'] ?? 'No description provided',
            'manager'                       => $data['manager'] ?? ProjectManager::createPartial([]),
            'maxWorkers'                    => $data['maxWorkers'] ?? WORKER_COUNT_MIN,
            'budget'                        => $data['budget'] ?? BUDGET_MIN,
            'tasks'                         => $data['tasks'] ?? null,
            'workers'                       => $data['workers'] ?? null,
            'phases'                        => $data['phases'] ?? null,
            'startDateTime'                 => $data['startDateTime'] ?? new DateTime(),
            'completionDateTime'            => $data['completionDateTime'] ?? new DateTime('+30 days'),
            'actualCompletionDateTime'      => $data['actualCompletionDateTime'] ?? null,
            'status'                        => $data['status'] ?? WorkStatus::PENDING,
            'additionalInfo'                => $data['additionalInfo'] ?? [],
            'createdAt'                     => $data['createdAt'] ?? null
        ];

        // Handle UUID conversion
        if (isset($data['publicId']) && !($data['publicId'] instanceof UUID))
            $defaults['publicId'] = UUID::tryFromString(trimOrNull($data['publicId']));

        // Handle Project Manager conversion
        if (isset($data['manager']) && is_array($data['manager']))
            $defaults['manager'] = ProjectManager::createPartial($data['manager']);

        // Handle TaskContainer conversion
        if (isset($data['tasks']) && !($data['tasks'] instanceof TaskContainer))
            $defaults['tasks'] = is_array($data['tasks'])
                ? TaskContainer::fromArray($data['tasks'])
                : null;

        // Handle WorkerContainer conversion
        if (isset($data['workers']) && !($data['workers'] instanceof WorkerContainer))
            $defaults['workers'] = is_array($data['workers'])
                ? WorkerContainer::fromArray($data['workers'])
                : new WorkerContainer();

        // Handle PhaseContainer conversion
        if (isset($data['phases']) && !($data['phases'] instanceof PhaseContainer))
            $defaults['phases'] = is_array($data['phases'])
                ? PhaseContainer::fromArray($data['phases'])
                : null;

        // Handle DateTime conversions
        if (isset($data['startDateTime']) && !($data['startDateTime'] instanceof DateTime))
            $defaults['startDateTime'] = new DateTime(trimOrNull($data['startDateTime']));

        if (isset($data['completionDateTime']) && !($data['completionDateTime'] instanceof DateTime))
            $defaults['completionDateTime'] = new DateTime(trimOrNull($data['completionDateTime']));

        if (isset($data['actualCompletionDateTime']) && !($data['actualCompletionDateTime'] instanceof DateTime))
            $defaults['actualCompletionDateTime'] = is_string($data['actualCompletionDateTime'])
                ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
                : null;

        if (isset($data['createdAt']) && !($data['createdAt'] instanceof DateTime))
            $defaults['createdAt'] = new DateTime(trimOrNull($data['createdAt']));

        // Handle enum conversions
        if (isset($data['status']) && !($data['status'] instanceof WorkStatus))
            $defaults['status'] = WorkStatus::from(trimOrNull($data['status']));

        // Create instance with default values
        $instance = new self(
            id: $defaults['id'],
            publicId: $defaults['publicId'],
            name: $defaults['name'],
            description: $defaults['description'],
            manager: $defaults['manager'],
            maxWorkers: $defaults['maxWorkers'],
            budget: $defaults['budget'],
            tasks: $defaults['tasks'],
            workers: $defaults['workers'],
            phases: $defaults['phases'],
            startDateTime: $defaults['startDateTime'],
            completionDateTime: $defaults['completionDateTime'],
            actualCompletionDateTime: $defaults['actualCompletionDateTime'],
            status: $defaults['status'],
            additionalInfo: $defaults['additionalInfo'],
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
     *      - maxWorkers: int Maximum number of workers
     *      - budget: float|int Project budget
     *      - tasks: array Collection of tasks as array
     *      - workers: array Collection of workers as array
     *      - phases: array Collection of project phases as array
     *      - startDateTime: string|null Formatted project start date/time
     *      - completionDateTime: string|null Formatted expected completion date/time
     *      - actualCompletionDateTime: string|null Formatted actual completion date/time
     *      - status: string Display name of the project status
     *      - createdAt: string Formatted creation date/time,
     *      - additionalInfo: array Additional project information
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $data = [
            'id'                        => UUID::toString($this->publicId),
            'name'                      => $this->name,
            'description'               => $this->description,
            'manager'                   => $this->manager->toArray($useSnakeCase),
            'maxWorkers'                => $this->maxWorkers,
            'budget'                    => $this->budget,
            'tasks'                     => $this->tasks?->toArray($useSnakeCase) ?? [],
            'workers'                   => $this->workers?->toArray($useSnakeCase) ?? [],
            'phases'                    => $this->phases?->toArray($useSnakeCase) ?? [],
            'startDateTime'             => formatDateTime($this->startDateTime, DateTime::ATOM),
            'completionDateTime'        => formatDateTime($this->completionDateTime, DateTime::ATOM),
            'actualCompletionDateTime'  =>
            $this->actualCompletionDateTime
                ? formatDateTime($this->actualCompletionDateTime, DateTime::ATOM)
                : null,
            'status'                    => $this->status->getDisplayName(),
            'createdAt'                 => $this->createdAt
                ? formatDateTime($this->createdAt)
                : null,
            'additionalInfo'            => $this->additionalInfo
        ];

        return $useSnakeCase ? normalizeArrayKeysToSnakeCase($data) : $data;
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
     *      - maxWorkers: int Maximum number of workers
     *      - budget: float|int Project budget
     *      - tasks: array|TaskContainer Project tasks
     *      - workers: array|WorkerContainer Project workers
     *      - phases: array|PhaseContainer Project phases
     *      - startDateTime: string|DateTime Project start date and time
     *      - completionDateTime: string|DateTime Expected project completion date and time
     *      - actualCompletionDateTime: string|DateTime Actual project completion date and time
     *      - status: string|WorkStatus Project work status
     *      - createdAt: string|DateTime Project creation timestamp
     *      - additionalInfo: array|mixed Additional project information
     * 
     * @return self New Project instance created from provided data
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

        $maxWorkers = !is_int($data['maxWorkers'])
            ? (int) $data['maxWorkers']
            : $data['maxWorkers'];

        $manager = (!$data['manager'] instanceof ProjectManager)
            ? ProjectManager::fromArray($data['manager'])
            : $data['manager'];

        $tasks = (!$data['tasks'] instanceof TaskContainer)
            ? TaskContainer::fromArray($data['tasks'])
            : $data['tasks'];

        $workers = (!$data['workers'] instanceof WorkerContainer)
            ? WorkerContainer::fromArray($data['workers'])
            : $data['workers'];

        $phases = (!$data['phases'] instanceof PhaseContainer)
            ? PhaseContainer::fromArray($data['phases'])
            : $data['phases'];

        $startDateTime = (\is_string($data['startDateTime']))
            ? new DateTime(trimOrNull($data['startDateTime']))
            : $data['startDateTime'];

        $completionDateTime = (\is_string($data['completionDateTime']))
            ? new DateTime(trimOrNull($data['completionDateTime']))
            : $data['completionDateTime'];

        $actualCompletionDateTime = (\is_string($data['actualCompletionDateTime']))
            ? new DateTime(trimOrNull($data['actualCompletionDateTime']))
            : $data['actualCompletionDateTime'];

        $status = (\is_string($data['status']))
            ? WorkStatus::fromString(trimOrNull($data['status']))
            : $data['status'];

        $createdAt = (\is_string($data['createdAt']))
            ? new DateTime(trimOrNull($data['createdAt']))
            : $data['createdAt'];

        return new Project(
            id: $data['id'],
            publicId: $publicId,
            name: trimOrNull($data['name']),
            description: trimOrNull($data['description']),
            manager: $manager,
            maxWorkers: $maxWorkers,
            budget: $data['budget'],
            tasks: $tasks,
            workers: $workers,
            phases: $phases,
            startDateTime: $startDateTime,
            completionDateTime: $completionDateTime,
            actualCompletionDateTime: $actualCompletionDateTime,
            status: $status,
            createdAt: $createdAt,
            additionalInfo: $data['additionalInfo'] ?? []
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
