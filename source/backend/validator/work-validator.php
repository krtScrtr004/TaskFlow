<?php

namespace App\Validator;

use App\Abstract\Validator;
use DateTime;

class WorkValidator extends Validator
{
    /**
     * Validate name
     */
    public function validateName(?string $name): void
    {
        if ($name === null || strlen(trim($name)) < 3 || strlen(trim($name)) > 255) {
            $this->errors['name'] = 'Name must be between 3 and 255 characters long.';
        }
    }

    /**
     * Validate description (optional)
     */
    public function validateDescription(?string $description): void
    {
        if ($description !== null && (strlen(trim($description)) < 5 || strlen(trim($description)) > 500)) {
            $this->errors['description'] = 'Description must be between 5 and 500 characters long.';
        }
    }

    /**
     * Validate budget
     */
    public function validateBudget($budget): void
    {
        if ($budget === null || !is_numeric($budget) || $budget < 0 || $budget > 1000000) {
            $this->errors['budget'] = 'Budget must be a number between 0 and 1,000,000.';
        }
    }

    /**
     * Validate start date and time
     */
    public function validateStartDateTime(?DateTime $startDateTime): void
    {
        if ($startDateTime === null) {
            $this->errors['startDateTime'] = 'Invalid start date and time.';
            return;
        }

        $currentDate = new DateTime();
        if ($startDateTime < $currentDate) {
            $this->errors['startDateTime'] = 'Start date cannot be in the past.';
        }
    }

    /**
     * Validate completion date and time
     */
    public function validateCompletionDateTime(?DateTime $completionDateTime, ?DateTime $startDateTime = null): void
    {
        if ($completionDateTime === null) {
            $this->errors['completionDateTime'] = 'Invalid completion date and time.';
            return;
        }

        if ($startDateTime !== null && $completionDateTime <= $startDateTime) {
            $this->errors['completionDateTime'] = 'Completion date must be after the start date.';
        }
    }

    // ------------------------------------------------------------------------------------------------------------------------------ //

    /**
     * Validate multiple work-related data
     * 
     * @param array $data Associative array containing work data to validate
     */
    public function validateMultiple(array $data): void
    {
        if (isset($data['name'])) {
            $this->validateName(trim($data['name']) ?? null);
        }

        if (isset($data['description'])) {
            $this->validateDescription(trim($data['description']) ?? null);
        }

        if (isset($data['budget'])) {
            $this->validateBudget($data['budget'] ?? null);
        }

        if (isset($data['startDateTime'])) {
            $this->validateStartDateTime($data['startDateTime'] ?? null);
        }

        if (isset($data['completionDateTime'])) {
            $this->validateCompletionDateTime(
                $data['completionDateTime'] ?? null,
                $data['startDateTime'] ?? null
            );
        }
    }
}