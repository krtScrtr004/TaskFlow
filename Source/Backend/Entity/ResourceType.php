<?php

namespace App\Entity;

use App\Exception\ValidationException;
use App\Interface\Entity;
use App\Validator\ResourceValidator;
use DateTime;

class ResourceType implements Entity {
    private int $id;
    private string $name;
    private string $description;
    private string $unit;
    private float $defaultRate;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    private ResourceValidator $resourceValidator;

    public function __construct(
        int $id,
        string $name,
        string $description,
        string $unit,
        float $defaultRate,
        DateTime $createdAt,
        DateTime|null $updatedAt = null
    ) {
        $this->resourceValidator = new ResourceValidator();
        $this->resourceValidator->validateMultiple([
            'name'          => trimOrNull($name),
            'description'   => trimOrNull($description),
            'unit'          => trimOrNull($unit),
            'defaultRate'   => $defaultRate
        ]);
        if ($this->resourceValidator->hasErrors()) {
            throw new ValidationException(
                'Resource Type Validation Failed',
                $this->resourceValidator->getErrors()
            );
        }

        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->unit = $unit;
        $this->defaultRate = $defaultRate;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // GETTERS

    /**
     * Gets the ID of the Resource Type.
     * 
     * @return int The ID of the Resource Type.
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Gets the name of the Resource Type.
     * 
     * @return string The name of the Resource Type.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Gets the description of the Resource Type.
     * 
     * @return string The description of the Resource Type.
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * Gets the unit of the Resource Type.
     * 
     * @return string The unit of the Resource Type.
     */
    public function getUnit(): string {
        return $this->unit;
    }

    /**
     * Gets the default rate of the Resource Type.
     * 
     * @return float The default rate of the Resource Type.
     */
    public function getDefaultRate(): float {
        return $this->defaultRate;
    }

    /**
     * Gets the creation date of the Resource Type.
     * 
     * @return DateTime The creation date of the Resource Type.
     */
    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    /**
     * Gets the last updated date of the Resource Type.
     * 
     * @return DateTime|null The last updated date of the Resource Type, or null if never updated.
     */
    public function getUpdatedAt(): DateTime|null {
        return $this->updatedAt;
    }

    // SETTERS

    /**
     * Sets the ID of the Resource Type.
     * 
     * @param int $id The ID to set.
     * 
     * @throws ValidationException If the ID is not a positive integer.
     */
    public function setId(int $id): void {
        if ( $id < 0) throw new ValidationException('Invalid ID');
        $this->id = $id;
    }

    /**
     * Sets the name of the Resource Type.
     * 
     * @param string $name The name to set.
     * 
     * @throws ValidationException If the name is invalid.
     */
    public function setName(string $name): void {
        $this->resourceValidator->validateName(trimOrNull($name));
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Name',
                $this->resourceValidator->getErrors()
            );
        $this->name = $name;
    }

    /**
     * Sets the description of the Resource Type.
     * 
     * @param string $description The description to set.
     * 
     * @throws ValidationException If the description is invalid.
     */
    public function setDescription(string $description): void {
        $this->resourceValidator->validateDescription(trimOrNull($description));
        if ($this->resourceValidator->hasErrors()) 
            throw new ValidationException(
                'Invalid Description',
                $this->resourceValidator->getErrors()
            );
        $this->description = $description;
    }

    /**
     * Sets the unit of the Resource Type.
     * 
     * @param string $unit The unit to set.
     * 
     * @throws ValidationException If the unit is invalid.
     */
    public function setUnit(string $unit): void {
        $this->resourceValidator->validateUnit(trimOrNull($unit));
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Unit',
                $this->resourceValidator->getErrors()
            );
        $this->unit = $unit;
    }

    /**
     * Sets the default rate of the Resource Type.
     * 
     * @param float $defaultRate The default rate to set.
     * 
     * @throws ValidationException If the default rate is invalid.
     */
    public function setDefaultRate(float $defaultRate): void {
        $this->resourceValidator->validateDefaultRate($defaultRate);
        if ($this->resourceValidator->hasErrors())
            throw new ValidationException(
                'Invalid Default Rate',
                $this->resourceValidator->getErrors()
            );
        $this->defaultRate = $defaultRate;
    }

    /**
     * Sets the creation date of the Resource Type.
     * 
     * @param DateTime $createdAt The creation date to set.
     */
    public function setCreatedAt(DateTime $createdAt): void {
        $this->createdAt = $createdAt;
    }

    /**
     * Sets the creation date of the Resource Type.
     * 
     * @param DateTime $createdAt The creation date to set.
     */
    public function setUpdatedAt(?DateTime $updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Creates a partial ResourceType instance from an array of data.
     * Missing fields are filled with default values.
     * 
     * @param array $data The data to create the ResourceType from.
     *  - name: string|null The name of the Resource Type (default: 'Unknown Resource Type').
     *  - description: string|null The description of the Resource Type (default: null).
     *  - category: string|null The category of the Resource Type (default: 'General').
     *  - unit: string|null The unit of the Resource Type (default: 'unit').
     *  - defaultRate: float|null The default rate of the Resource Type (default: 0.0).
     *  - createdAt: string|null The creation date of the Resource Type in ISO 8601 format (default: current date and time).
     *  - updatedAt: string|null The last update date of the Resource Type in ISO 8601 format (default: null).
     * 
     * @return ResourceType The created ResourceType instance.
     */
    public static function createPartial(array $data): ResourceType
    {
        $data = normalizeArrayKeysToCamelCase($data);

        $defaults = [
            'id'            => $data['id'] ?? 0,
            'name'          => $data['name'] ?? 'Unknown Resource Type',
            'description'   => $data['description'] ?? '',
            'category'      => $data['category'] ?? 'General',
            'unit'          => $data['unit'] ?? 'unit',
            'defaultRate'   => $data['defaultRate'] ?? DEFAULT_RATE_MIN,
            'createdAt'     => isset($data['createdAt']) 
                                ? new DateTime($data['createdAt']) 
                                : new DateTime(),
            'updatedAt'     => isset($data['updatedAt']) 
                                ? new DateTime($data['updatedAt']) 
                                : null,
        ];

        return new ResourceType(
            id: $defaults['id'],
            name: $defaults['name'],
            description: $defaults['description'],
            unit: $defaults['unit'],
            defaultRate: $defaults['defaultRate'],
            createdAt: $defaults['createdAt'],
            updatedAt: $defaults['updatedAt']
        );
    }

    /**
     * Creates a ResourceType instance from an array of data.
     * 
     * @param array $data The data to create the ResourceType from.
     *  - id: int The ID of the Resource Type.
     *  - name: string The name of the Resource Type.
     *  - description: string The description of the Resource Type.
     *  - category: string The category of the Resource Type.
     *  - unit: string The unit of the Resource Type.
     *  - defaultRate: float The default rate of the Resource Type.
     *  - createdAt: string The creation date of the Resource Type in ISO 8601 format.
     *  - updatedAt: string|null The last update date of the Resource Type in ISO 8601 format.
     * 
     * @return ResourceType The created ResourceType instance.
     */
    public static function fromArray(array $data): ResourceType
    {
        $data = normalizeArrayKeysToCamelCase($data);

        return new ResourceType(
            $data['id'],
            $data['name'],
            $data['description'],
            $data['unit'],
            $data['defaultRate'],
            new DateTime($data['createdAt']),
            isset($data['updatedAt']) 
                ? new DateTime($data['updatedAt']) 
                : null
        );
    }
    
    /**
     * Converts the ResourceType instance to an array.
     * 
     * @param bool $useSnakeCase Whether to use snake_case keys (default: false).
     * 
     * @return array The ResourceType data as an array.
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $data =  [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'unit'          => $this->unit,
            'defaultRate'   => $this->defaultRate,
            'createdAt'     => $this->createdAt->format('c'),
            'updatedAt'     => $this->updatedAt ? $this->updatedAt->format('c') : null,
        ];

        return $useSnakeCase ? normalizeArrayKeysToSnakeCase($data) : $data;
    }

    /**
     * Specifies data which should be serialized to JSON.
     * 
     * @return array The data to be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}