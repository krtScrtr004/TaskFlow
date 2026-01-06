<?php

namespace App\Validator;

use App\Abstract\Validator;

class ResourceValidator extends Validator
{
    /**
     *  Validates the name of a resource.
     * 
     * This method checks if the provided resource name meets the defined length
     * constraints and does not contain consecutive special characters. If the name
     * is invalid, appropriate error messages are added to the errors array.
     * 
     * @param string $name The name of the resource.
     * 
     * @return void
     */
    public function validateName(string $name): void
    {
        $this->iValidateName($name);
    }

    /**
     * Validates the description of a resource.
     * 
     * This method checks if the provided resource description meets the defined length
     * constraints and does not contain consecutive special characters. If the description
     * is invalid, appropriate error messages are added to the errors array.
     * 
     * @param string $description The description of the resource.
     * 
     * @return void
     */
    public function validateDescription(string $description): void
    {
        $this->iValidateLongMessage($description, ['fieldLabel' => 'Description']);
    }

    /**
     * Validates the category of a resource.
     * 
     * This method checks if the provided resource category meets the defined length
     * constraints and does not contain consecutive special characters. If the category
     * is invalid, appropriate error messages are added to the errors array.
     * 
     * @param string $category The category of the resource.
     * 
     * @return void
     */
    public function validateCategory(string $category): void
    {
        $category = trim($category);
        $min = NAME_MIN;
        $max = NAME_MAX;

        if (!$category || strlen($category) < $min || strlen($category) > $max)
            $this->errors[] = "Resource category must be between {$min} and {$max} characters.";

        if ($this->hasConsecutiveSpecialChars($category))
            $this->errors[] = "Resource category must not contain consecutive special characters.";
    }

    /**
     * Validates the unit of a resource.
     * 
     * This method checks if the provided resource unit meets the defined length
     * constraints and does not contain consecutive special characters. If the unit
     * is invalid, appropriate error messages are added to the errors array.
     * 
     * @param string $unit The unit of the resource.
     * 
     * @return void
     */
    public function validateUnit(string $unit): void
    {
        $unit = trim($unit);
        $min = UNIT_NAME_MIN;
        $max = UNIT_NAME_MAX;

        if (!$unit || strlen($unit) < $min || strlen($unit) > $max)
            $this->errors[] = "Resource unit must be between {$min} and {$max} characters.";

        if ($this->hasConsecutiveSpecialChars($unit))
            $this->errors[] = "Resource unit must not contain consecutive special characters.";
    }

    /**
     * Validates the default rate of a resource.
     * 
     * This method checks if the provided default rate falls within the acceptable
     * range defined by DEFAULT_RATE_MIN and DEFAULT_RATE_MAX constants.
     * If the value is outside this range, an error message is added to the errors array.
     * 
     * @param float $defaultRate The default rate of the resource.
     * 
     * @return void
     */
    public function validateDefaultRate(float $defaultRate): void
    {
        $this->iValidateDefaultRate($defaultRate);
    }

    /**
     * Validates the hours assigned to a worker.
     *
     * This method checks if the provided hours assigned value falls within the acceptable
     * range defined by WORKER_HOURS_MIN and WORKER_HOURS_MAX constants.
     * If the value is outside this range, an error message is added to the errors array.
     *
     * @param float $hoursAssigned The number of hours assigned.
     * 
     * @return void
     */
    public function validateHoursAssigned(float $hoursAssigned): void
    {
        $min = WORKER_HOURS_MIN;
        $max = WORKER_HOURS_MAX;

        if ($hoursAssigned < $min || $hoursAssigned > $max) {
            $this->errors[] = "Hours assigned must be between {$min} and {$max}.";
        }
    }

    /**
     * Validates the quantity of a resource.
     * 
     * This method checks if the provided resource quantity falls within the acceptable
     * range defined by RESOURCE_QUANTITY_MIN and RESOURCE_QUANTITY_MAX constants.
     * If the value is outside this range, an error message is added to the errors array.
     * 
     * @param int $quantity The quantity of the resource.
     * 
     * @return void
     */
    public function validateQuantity(int $quantity): void
    {
        $min = RESOURCE_QUANTITY_MIN;
        $max = RESOURCE_QUANTITY_MAX;

        if ($quantity < $min || $quantity > $max)
            $this->errors[] = "Resource quantity must be between {$min} and {$max}.";

        // TODO: Check the price per unit and see if the total cost exceeds some phase budget limit
    }

    // ------------------------------------------------------------------------------------------------------------------------------ //

    /**
     * Validates multiple resource attributes.
     * 
     * This method accepts an associative array of resource attributes and validates
     * each attribute using the corresponding validation methods. It checks for the presence
     * of each attribute in the array before invoking its validation method.
     * 
     * 
     * @param array $data An associative array containing resource attributes to validate.
     *  - name: string The name of the resource.
     *  - description: string The description of the resource.
     *  - category: string The category of the resource.
     *  - unit: string The unit of the resource.
     *  - defaultRate: float The default rate of the resource.
     *  - hoursAssigned: float The number of hours assigned.
     *  - quantity: int The quantity of the resource.
     * 
     * @return void
     */
    public function validateMultiple(array $data): void
    {
        if (isset($data['name']))
            $this->validateName((string) $data['name']);

        if (isset($data['description']))
            $this->validateDescription((string) $data['description']);

        if (isset($data['category']))
            $this->validateCategory((string) $data['category']);

        if (isset($data['unit']))
            $this->validateUnit((string) $data['unit']);

        if (isset($data['defaultRate']))
            $this->validateDefaultRate((float) $data['defaultRate']);

        if (isset($data['hoursAssigned']))
            $this->validateHoursAssigned((float) $data['hoursAssigned']);

        if (isset($data['quantity']))
            $this->validateQuantity((int) $data['quantity']);
    }
}
