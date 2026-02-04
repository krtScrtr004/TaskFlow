<?php

namespace App\Entity;

use App\Core\UUID;
use App\Entity\ResourceType;
use App\Exception\ValidationException;
use App\Interface\Entity;
use App\Validator\ResourceValidator;
use DateTime;

class TaskResource implements Entity 
{
    private int $id;
    private UUID $publicId;
    private ResourceType $type;
    private int|null $taskWorkerId; // Optional - links to phase_task_worker for labor resources
    private int $quantity; 
    private float $unitRate; // Cost per unit
    private float $estimatedUnit; // Estimate quantity 
    private float $actualUnit; // Actual quantity used
    private string|null $note;
    private DateTime|null $createdAt;

    private ResourceValidator $resourceValidator;

    /**
     * TaskResource constructor.
     * 
     * @param int $id The ID of the task resource.
     * @param UUID $publicId The public UUID of the task resource.
     * @param ResourceType $type The type of the resource.
     * 
     * OPTIONAL / WITH DEFAULT VALUES:
     * @param int $quantity The quantity of the resource.
     * @param float $unitRate The unit rate of the resource.
     * @param float|null $estimatedUnit The estimated unit of the resource.
     * @param float|null $actualUnit The actual unit of the resource.
     * @param string|null $note The note of the resource.
     * @param int|null $taskWorkerId Optional link to phase_task_worker (for labor resources).
     * @param DateTime|null $createdAt Optional creation timestamp (nullable).
     * 
     * @throws ValidationException If any of the provided values are invalid.
     */
    public function __construct(
        int $id,
        UUID $publicId,
        ResourceType $type,

        int $quantity = RESOURCE_QUANTITY_MIN,
        float $unitRate = DEFAULT_RATE_MIN,
        float|null $estimatedUnit = null,
        float|null $actualUnit = null,
        string|null $note = null,
        int|null $taskWorkerId = null,
        DateTime|null $createdAt = null
    ) {
        $this->resourceValidator = new ResourceValidator();
        $this->resourceValidator->validateMultiple([
            'quantity'      => $quantity,
            'unitRate'      => $unitRate,
            'estimatedUnit' => $estimatedUnit,
            'actualUnit'    => $actualUnit,
            'note'          => $note
        ]);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Resource Validation Failed',
                $this->resourceValidator->getErrors()
            );

        $this->id = $id;
        $this->publicId = $publicId;
        $this->type = $type;
        $this->taskWorkerId = $taskWorkerId;
        $this->quantity = $quantity;
        $this->unitRate = $unitRate;
        $this->estimatedUnit = $estimatedUnit;
        $this->actualUnit = $actualUnit;
        $this->note = $note;
        $this->createdAt = $createdAt ?? new DateTime();
    }

    // GETTERS

    /**
     * Gets the ID of the resource.
     * 
     * @return int The ID of the resource.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the public UUID of the resource.
     * 
     * @return UUID The public UUID of the resource.
     */
    public function getPublicId(): UUID
    {
        return $this->publicId;
    }

    /**
     * Gets the type of the resource.
     * 
     * @return ResourceType The type of the resource.
     */    
    public function getType(): ResourceType
    {
        return $this->type;
    }

    /**
     * Gets the quantity of the resource.
     * 
     * @return int The quantity of the resource.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Gets the unit rate of the resource.
     * 
     * @return float The unit rate of the resource.
     */
    public function getUnitRate(): float
    {
        return $this->unitRate;
    }

    /**
     * Gets the estimated unit of the resource.
     * 
     * @return float The estimated unit of the resource.
     */
    public function getEstimatedUnit(): float
    {
        return $this->estimatedUnit;
    }

    /**
     * Gets the actual unit of the resource.
     * 
     * @return float The actual unit of the resource.
     */
    public function getActualUnit(): float
    {
        return $this->actualUnit;
    }

    /**
     * Gets the note of the resource.
     * 
     * @return string|null The note of the resource.
     */
    public function getNote(): string|null
    {
        return $this->note;
    }

    /**
     * Gets the task worker ID (for labor resources).
     * 
     * @return int|null The task worker ID.
     */
    public function getTaskWorkerId(): int|null
    {
        return $this->taskWorkerId;
    }

    /**
     * Gets the creation timestamp of the resource.
     * 
     * @return DateTime|null The creation timestamp.
     */
    public function getCreatedAt(): DateTime|null
    {
        return $this->createdAt;
    }

    // SETTERS

    /**
     * Sets the ID of the resource.
     * 
     * @param int $id The ID of the resource.
     * 
     * @return void
     * 
     * @throws ValidationException If the ID is invalid.
     */
    public function setId(int $id): void
    {
        if ($id <= 0) throw new ValidationException('Invalid ID');
        $this->id = $id;
    }

    /**
     * Sets the public UUID of the resource.
     * 
     * @param UUID $publicId The public UUID of the resource.
     * 
     * @return void
     */
    public function setPublicId(UUID $publicId): void
    {
        $this->publicId = $publicId;
    }

    /**
     * Sets the type of the resource.
     * 
     * @param ResourceType $type The type of the resource.
     * 
     * @return void
     */
    public function setType(ResourceType $type): void
    {
        $this->type = $type;
    }

    /**
     * Sets the quantity of the resource.
     * 
     * @param int $quantity The quantity of the resource.
     * 
     * @return void
     * 
     * @throws ValidationException If the quantity is invalid.
     */
    public function setQuantity(int $quantity): void
    {
        $this->resourceValidator->validateQuantity($quantity);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Quantity',
                $this->resourceValidator->getErrors()
            );
        $this->quantity = $quantity;
    }

    /**
     * Sets the unit rate of the resource.
     * 
     * @param float $unitRate The unit rate of the resource.
     * 
     * @return void
     * 
     * @throws ValidationException If the unit rate is invalid.
     */
    public function setUnitRate(float $unitRate): void
    {
        $this->resourceValidator->validateUnitRate($unitRate);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Unit Rate',
                $this->resourceValidator->getErrors()
            );
        $this->unitRate = $unitRate;
    }

    /**
     * Sets the estimated unit of the resource.
     * 
     * @param float $estimatedUnit The estimated unit of the resource.
     * 
     * @return void
     * 
     * @throws ValidationException If the estimated unit is invalid.
     */
    public function setEstimatedUnit(float $estimatedUnit): void
    {
        $this->resourceValidator->validateEstimatedUnit($estimatedUnit);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Estimated Unit',
                $this->resourceValidator->getErrors()
            );  
        $this->estimatedUnit = $estimatedUnit;
    }

    /**
     * Sets the actual unit of the resource.
     * 
     * @param float $actualUnit The actual unit of the resource.
     * 
     * @return void
     * 
     * @throws ValidationException If the actual unit is invalid.
     */
    public function setActualUnit(float $actualUnit): void
    {
        $this->resourceValidator->validateActualUnit($actualUnit);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Actual Unit',
                $this->resourceValidator->getErrors()
            );  
        $this->actualUnit = $actualUnit;
    }

    /**
     * Sets the note of the resource.
     * 
     * @param string|null $note The note of the resource.
     * 
     * @return void
     * 
     * @throws ValidationException If the note is invalid.
     */    
    public function setNote(string|null $note): void
    {
        $this->resourceValidator->validateNote($note);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Note',
                $this->resourceValidator->getErrors()
            );  
        $this->note = $note;
    }

    /**
     * Sets the task worker ID.
     * 
     * @param int|null $taskWorkerId The task worker ID.
     * 
     * @return void
     */
    public function setTaskWorkerId(int|null $taskWorkerId): void
    {
        $this->taskWorkerId = $taskWorkerId;
    }

    /**
     * Sets the creation timestamp of the resource.
     * 
     * @param DateTime|null $createdAt The creation timestamp.
     * 
     * @return void
     */
    public function setCreatedAt(DateTime|null $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // OTHER METHODS (UTILITIES)

    /**
     * Creates a partial TaskResource instance from the provided data.
     * 
     * This method allows for the creation of a TaskResource object using only
     * a subset of its properties. Missing properties are filled with existing
     * values from the current instance or default values.
     * 
     * @param array $data An associative array containing the properties to set.
     * 
     * @return TaskResource A new TaskResource instance with the specified properties.
     */
    public static function createPartial(array $data): TaskResource
    {
        $data = normalizeArrayKeysToCamelCase($data);
        
        $defaults = [
            'id'            => $data['id'] ?? mt_rand(),
            'publicId'      => $data['publicId'] ?? UUID::get(),
            'type'          => $data['type'] ?? ResourceType::createPartial([]),
            'quantity'      => $data['quantity'] ?? 0,
            'unitRate'      => $data['unitRate'] ?? 0.0,
            'estimatedUnit' => $data['estimatedUnit'] ?? 0.0,
            'actualUnit'    => $data['actualUnit'] ?? 0.0,
            'note'          => $data['note'] ?? null,
            'taskWorkerId'  => $data['taskWorkerId'] ?? null
        ];

        // Build ResourceType instance from array
        if (isset($data['type']) && is_array($data['type'])) 
            $defaults['type'] = ResourceType::fromArray($data['type']);

        return new TaskResource(
            id: $defaults['id'],
            publicId: $defaults['publicId'],
            type: $defaults['type'],
            quantity: $defaults['quantity'],
            unitRate: $defaults['unitRate'],
            estimatedUnit: $defaults['estimatedUnit'],
            actualUnit: $defaults['actualUnit'],
            note: $defaults['note'],
            taskWorkerId: $defaults['taskWorkerId']
        );
    }

    /**
     * Creates a TaskResource instance from an associative array.
     * 
     * This static method constructs a TaskResource object using the provided
     * associative array, mapping the array keys to the corresponding
     * properties of the TaskResource class.
     * 
     * @param array $data An associative array containing the resource data.
     * 
     * @return TaskResource A new TaskResource instance created from the array data.
     */
    public static function fromArray(array $data): TaskResource
    {
        $data = normalizeArrayKeysToCamelCase($data);

        return new TaskResource(
            id: $data['id'],
            publicId: UUID::fromString($data['publicId']),
            type: ResourceType::fromArray($data['type']),
            quantity: $data['quantity'],
            unitRate: $data['unitRate'],
            estimatedUnit: $data['estimatedUnit'] ?? null,
            actualUnit: $data['actualUnit'] ?? null,
            note: $data['note'] ?? null,
            taskWorkerId: $data['taskWorkerId'] ?? null
        );
    }

    /**
     * Converts the TaskResource instance to an associative array.
     * 
     * This method serializes the TaskResource object into an associative array,
     * with an option to format the keys in snake_case.
     * 
     * @param bool $useSnakeCase Whether to use snake_case for the array keys.
     * 
     * @return array An associative array representation of the TaskResource instance.
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $data = [
            'id'            => $this->id,
            'publicId'      => UUID::toString($this->publicId),
            'type'          => $this->type->toArray(),
            'taskWorkerId'  => $this->taskWorkerId,
            'quantity'      => $this->quantity,
            'unitRate'      => $this->unitRate,
            'estimatedUnit' => $this->estimatedUnit,
            'actualUnit'    => $this->actualUnit,
            'note'          => $this->note
        ];

        return $useSnakeCase ? normalizeArrayKeysToSnakeCase($data) : $data;
    }

    /**
     * Specifies data which should be serialized to JSON.
     * 
     * This method is called by json_encode() and returns an array
     * representation of the Resource instance for JSON serialization.
     * 
     * @return array An associative array representation of the Resource instance.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}