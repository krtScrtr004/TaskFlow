<?php 

namespace App\Entity\Dependent;

use App\Container\PhaseContainer;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Enumeration\WorkStatus;
use App\Exception\ValidationException;
use App\Validator\UuidValidator;
use App\Validator\WorkValidator;
use DateTime;

/**
 * Class ProjectReport
 *
 * Lightweight dependent entity used for assembling a project report containing
 * summary information about a project, its phases and participating workers.
 */
class ProjectReport {
    private int $id;
    private UUID $publicId;
    private string $name;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;
    private PhaseContainer $phases;
    private WorkerContainer $workers;

    private WorkValidator $validator;

    /**
     * ProjectReport constructor.
     *
     * Validates provided input via WorkValidator and initializes the report
     * instance with project metadata, phases and worker containers.
     *
     * @param int $id Internal project ID
     * @param UUID $publicId Public UUID for the project
     * @param string $name Project name
     * @param DateTime $startDateTime Project planned start
     * @param DateTime $completionDateTime Project planned completion
     * @param ?DateTime $actualCompletionDateTime Actual completion timestamp or null
     * @param WorkStatus $status Current project status
     * @param PhaseContainer $phases Container of project phases
     * @param WorkerContainer $workers Container of project workers
     *
     * @throws ValidationException when validation fails
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        WorkStatus $status,
        PhaseContainer $phases,
        WorkerContainer $workers
    ) {
        $this->validator = new WorkValidator();
        $this->validator->validateMultiple([
            'name' => $name,
            'startDateTime' => $startDateTime,
            'completionDateTime' => $completionDateTime
        ]);
        if ($this->validator->hasErrors()) {
            throw new ValidationException("Invalid data provided for ProjectReport");
        }

        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = $name;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
        $this->phases = $phases;
        $this->workers = $workers;
    }

    // Getters 
    /**
     * Get internal project ID.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get project public UUID.
     *
     * @return UUID
     */
    public function getPublicId(): UUID
    {
        return $this->publicId;
    }

    /**
     * Get project name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get project start DateTime.
     *
     * @return DateTime
     */
    public function getStartDateTime(): DateTime
    {
        return $this->startDateTime;
    }

    /**
     * Get project planned completion DateTime.
     *
     * @return DateTime
     */
    public function getCompletionDateTime(): DateTime
    {
        return $this->completionDateTime;
    }

    /**
     * Get actual completion DateTime, or null if not completed.
     *
     * @return DateTime|null
     */
    public function getActualCompletionDateTime(): ?DateTime
    {
        return $this->actualCompletionDateTime;
    }

    /**
     * Get current project status.
     *
     * @return WorkStatus
     */
    public function getStatus(): WorkStatus
    {
        return $this->status;
    }

    /**
     * Get container of project phases.
     *
     * @return PhaseContainer
     */
    public function getPhases(): PhaseContainer
    {
        return $this->phases;
    }

    /**
     * Get container of project workers.
     *
     * @return WorkerContainer
     */
    public function getWorkers(): WorkerContainer
    {
        return $this->workers;
    }


    // Setters 
    /**
     * Set internal project ID.
     *
     * @param int $id
     * @throws ValidationException when id is not positive
     * @return void
     */
    public function setId(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException("ID must be a positive integer");
        }
        $this->id = $id;
    }

    /**
     * Set the project's public UUID.
     *
     * Validates the UUID before assignment.
     *
     * @param UUID $publicId
     * @throws ValidationException when UUID is invalid
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
     * Set the project name after validation.
     *
     * @param string $name
     * @throws ValidationException when name fails validation
     * @return void
     */
    public function setName(string $name): void
    {
        $this->validator->validateName(trim($name));
        if ($this->validator->hasErrors()) {
            throw new ValidationException("Invalid project name", $this->validator->getErrors());
        }
        $this->name = trimOrNull($name);
    }

    /**
     * Set project start DateTime after validation.
     *
     * @param DateTime $startDateTime
     * @throws ValidationException when start date is invalid
     * @return void
     */
    public function setStartDateTime(DateTime $startDateTime): void
    {
        $this->validator->validateStartDateTime($startDateTime);
        if ($this->validator->hasErrors()) {
            throw new ValidationException("Invalid start date", $this->validator->getErrors());
        }
        $this->startDateTime = $startDateTime;
    }

    /**
     * Set planned completion DateTime. Validates against startDateTime.
     *
     * @param DateTime $completionDateTime
     * @throws ValidationException when completion date is invalid
     * @return void
     */
    public function setCompletionDateTime(DateTime $completionDateTime): void
    {
        $this->validator->validateCompletionDateTime($completionDateTime, $this->startDateTime);
        if ($this->validator->hasErrors()) {
            throw new ValidationException("Invalid completion date", $this->validator->getErrors());
        }
        $this->completionDateTime = $completionDateTime;
    }

    /**
     * Set actual completion DateTime (nullable).
     *
     * @param DateTime|null $actualCompletionDateTime
     * @return void
     */
    public function setActualCompletionDateTime(?DateTime $actualCompletionDateTime): void
    {
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    /**
     * Set project status.
     *
     * @param WorkStatus $status
     * @return void
     */
    public function setStatus(WorkStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Set the PhaseContainer for this report.
     *
     * @param PhaseContainer $phases
     * @return void
     */
    public function setPhases(PhaseContainer $phases): void
    {
        $this->phases = $phases;
    }

    /**
     * Set the WorkerContainer for this report.
     *
     * @param WorkerContainer $workers
     * @return void
     */
    public function setWorkers(WorkerContainer $workers): void
    {
        $this->workers = $workers;
    }
}