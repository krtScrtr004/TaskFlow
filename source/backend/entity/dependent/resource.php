<?php

use App\Dependent;
use App\Exception\ValidationException;
use App\Interface\Entity;
use App\Validator\ResourceValidator;

class Resource implements Entity {
    private int $id;
    private ResourceType $type;
    private int $quantity;
    private float $unitRate;
    private float $estimatedUnit;
    private ?string $note;

    private ResourceValidator $resourceValidator;


    /**
     * Resource constructor.
     * 
     * @param int $id The ID of the resource.
     * @param ResourceType $type The type of the resource.
     * @param int $quantity The quantity of the resource.
     * @param float $unitRate The unit rate of the resource.
     * @param float|null $estimatedUnit The estimated unit of the resource.
     * @param string|null $note The note of the resource.
     * 
     * @throws ValidationException If any of the provided values are invalid.
     */
    public function __construct(
        int $id,
        ResourceType $type,
        int $quantity,
        float $unitRate,
        ?float $estimatedUnit = null,
        ?string $note = null
    ) {
        $this->resourceValidator = new ResourceValidator();
        $this->resourceValidator->validateMultiple([
            'quantity' => $quantity,
            'unitRate' => $unitRate,
            'note' => $note
        ]);
        if (isset($estimatedUnit)) 
            $this->resourceValidator->validateEstimatedUnit($estimatedUnit);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                'Resource Validation Failed.',
                $this->resourceValidator->getErrors()
            );
        }

        $this->id = $id;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->unitRate = $unitRate;
        $this->estimatedUnit = isset($estimatedUnit)
            ? $estimatedUnit
            : (float) $quantity * $unitRate;
        $this->note = $note;
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
     * Gets the note of the resource.
     * 
     * @return string|null The note of the resource.
     */
    public function getNote(): ?string
    {
        return $this->note;
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
        if ($id <= 0) 
            throw new ValidationException('Invalid Resource ID');
        $this->id = $id;
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
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                'Invalid Resource Quantity',
                $this->resourceValidator->getErrors()
            );
        }
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
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                'Invalid Resource Unit Rate',
                $this->resourceValidator->getErrors()
            );
        }
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
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                'Invalid Resource Estimated Unit',
                $this->resourceValidator->getErrors()
            );
        }
        $this->estimatedUnit = $estimatedUnit;
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
    public function setNote(?string $note): void
    {
        $this->resourceValidator->validateNote($note);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                'Invalid Resource Note',
                $this->resourceValidator->getErrors()
            );  
        }
        $this->note = $note;
    }

    // OTHER METHODS (UTILITIES)

    /**
     * Creates a partial Resource instance from the provided data.
     * 
     * This method allows for the creation of a Resource object using only
     * a subset of its properties. Missing properties are filled with existing
     * values from the current instance or default values.
     * 
     * @param array $data An associative array containing the properties to set.
     * 
     * @return Resource A new Resource instance with the specified properties.
     */
    public function createPartial(array $data): Resource
    {
        $data = normalizeArrayKeysToCamelCase($data);
        
        
        $defaults = [
            'id'            => $this->id ?? 0,
            'type'          => $this->type ?? ResourceType::createPartial([]),
            'quantity'      => $this->quantity ?? 0,
            'unitRate'      => $this->unitRate ?? 0.0,
            'estimatedUnit' => $this->estimatedUnit ?? 0.0,
            'note'          => $this->note ?? null
        ];

        return new Resource(
            $defaults['id'],
            $defaults['type'],
            $defaults['quantity'],
            $defaults['unitRate'],
            $defaults['estimatedUnit'],
            $defaults['note']
        );
    }

    /**
     * Creates a Resource instance from an associative array.
     * 
     * This static method constructs a Resource object using the provided
     * associative array, mapping the array keys to the corresponding
     * properties of the Resource class.
     * 
     * @param array $data An associative array containing the resource data.
     * 
     * @return Resource A new Resource instance created from the array data.
     */
    public static function fromArray(array $data): Resource
    {
        $data = normalizeArrayKeysToCamelCase($data);

        return new Resource(
            $data['id'],
            ResourceType::fromArray($data['type']),
            $data['quantity'],
            $data['unitRate'],
            $data['estimatedUnit'] ?? null,
            $data['note'] ?? null
        );
    }

    /**
     * Converts the Resource instance to an associative array.
     * 
     * This method serializes the Resource object into an associative array,
     * with an option to format the keys in snake_case.
     * 
     * @param bool $useSnakeCase Whether to use snake_case for the array keys.
     * 
     * @return array An associative array representation of the Resource instance.
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $data = [
            'id'            => $this->id,
            'type'          => $this->type->toArray(),
            'quantity'      => $this->quantity,
            'unitRate'      => $this->unitRate,
            'estimatedUnit' => $this->estimatedUnit,
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