<?php

namespace App\Dependent;

use App\Interface\Entity;
use App\Enumeration\WorkStatus;
use App\Core\UUID;
use App\Exception\ValidationException;
use App\Validator\WorkValidator;
use DateTime;

class Phase implements Entity
{
    private int $id;
    private UUID $publicId;
    private string $name;
    private string $description;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;

    protected WorkValidator $workValidator;

    /**
     * Phase constructor.
     * 
     * Creates a new Phase instance with the provided details.
     * All parameters are validated through WorkValidator before assignment.
     * 
     * @param int $id The unique identifier for the phase in the database
     * @param UUID $publicId The public identifier for the phase
     * @param string $name Phase name (3-255 characters)
     * @param string $description Phase description (5-500 characters)
     * @param DateTime $startDateTime Phase start date and time (cannot be in the past)
     * @param DateTime $completionDateTime Expected phase completion date and time (must be after start date)
     * @param DateTime|null $actualCompletionDateTime Actual completion date and time (null if not completed)
     * @param WorkStatus $status Current status of the phase (enum)
     * 
     * @throws ValidationException If any of the provided data fails validation
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $name,
        string $description,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        WorkStatus $status
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
                throw new ValidationException("Phase validation failed", $this->workValidator->getErrors());
            }
        } catch (ValidationException $th) {
            throw $th;
        }

        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = $name;
        $this->description = $description;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
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
    public function getDescription(): string
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

    /**
     * Gets the actual completion date and time.
     *
     * @return DateTime|null The DateTime object representing when the phase was completed, or null if not completed
     */
    public function getActualCompletionDateTime(): ?DateTime
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
        if ($id < 0) {
            throw new ValidationException("Invalid phase ID");
        }
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
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid phase name", $this->workValidator->getErrors());
        }
        $this->name = $name;
    }

    /**
     * Sets the phase's description.
     *
     * @param string $description The description to set (5-500 characters, optional)
     * @throws ValidationException If the description is invalid
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->workValidator->validateDescription(trim($description));
        if ($this->workValidator->hasErrors()) {
            throw new ValidationException("Invalid phase description", $this->workValidator->getErrors());
        }
        $this->description = $description;
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
     * Sets the phase status.
     *
     * @param WorkStatus $status The WorkStatus enum value to set
     * @return void
     */
    public function setStatus(WorkStatus $status): void
    {
        $this->status = $status;
    }

    // Other methods (Utility)

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
     */
    public function toArray(): array
    {
        return [
            'id' => $this->publicId,
            'name' => $this->name,
            'description' => $this->description,
            'startDateTime' => formatDateTime($this->startDateTime, DateTime::ATOM),
            'completionDateTime' => formatDateTime($this->completionDateTime, DateTime::ATOM),
            'actualCompletionDateTime' => $this->actualCompletionDateTime ? formatDateTime($this->actualCompletionDateTime, DateTime::ATOM) : null,
            'status' => $this->status->value
        ];
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
     * 
     * @return self New Phase instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        $publicId = null;
        if ($data['publicId'] instanceof UUID) {
            $publicId = $data['publicId'];
        } else if (is_string($data['publicId'])) {
            $publicId = UUID::fromBinary($data['publicId']);
        }

        $startDateTime = (is_string($data['startDateTime']))
            ? new DateTime($data['startDateTime'])
            : $data['startDateTime'];

        $completionDateTime = (is_string($data['completionDateTime']))
            ? new DateTime($data['completionDateTime'])
            : $data['completionDateTime'];

        $actualCompletionDateTime = (is_string($data['actualCompletionDateTime']))
            ? new DateTime($data['actualCompletionDateTime'])
            : $data['actualCompletionDateTime'];

        $status = (is_string($data['status']))
            ? WorkStatus::fromString($data['status'])
            : $data['status'];

        return new self(
            id: $data['id'],
            publicId: $publicId,
            name: $data['name'],
            description: $data['description'],
            startDateTime: $startDateTime,
            completionDateTime: $completionDateTime,
            actualCompletionDateTime: $actualCompletionDateTime,
            status: $status
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