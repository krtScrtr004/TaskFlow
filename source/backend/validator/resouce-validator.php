<?php 

namespace App\Validator;

use App\Abstract\Validator;

class ResourceValidator extends Validator
{
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

    // ------------------------------------------------------------------------------------------------------------------------------ //

    /**
     * Validates multiple resource-related fields.
     *
     * @param array $data The data to validate.
     * 
     * @return void
     */
    public function validateMultiple(array $data): void
    {
        if (isset($data['hoursAssigned'])) {
            $this->validateHoursAssigned((float) $data['hoursAssigned']);
        }
    }
}