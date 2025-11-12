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
        if ($name === null || strlen(trim($name)) < NAME_MIN || strlen(trim($name)) > NAME_MAX) {
            $this->errors[] = 'Name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }
    }

    /**
     * Validate description (optional)
     */
    public function validateDescription(?string $description): void
    {
        if ($description !== null && (strlen(trim($description)) < LONG_TEXT_MIN || strlen(trim($description)) > LONG_TEXT_MAX)) {
            $this->errors[] = 'Description must be between ' . LONG_TEXT_MIN . ' and ' . LONG_TEXT_MAX . ' characters long.';
        }
    }

    /**
     * Validate budget
     */
    public function validateBudget($budget): void
    {
        if ($budget === null || !is_numeric($budget) || $budget < BUDGET_MIN || $budget > BUDGET_MAX) {
            $this->errors[] = 'Budget must be a number between ' . BUDGET_MIN . ' and ' . BUDGET_MAX . '.';
        }
    }

    /**
     * Validate start date and time
     */
    public function validateStartDateTime(?DateTime $startDateTime): void
    {
        if ($startDateTime === null) {
            $this->errors[] = 'Invalid start date and time.';
            return;
        }

        if (checkdate((int) $startDateTime->format('m'), (int) $startDateTime->format('d'), (int) $startDateTime->format('Y')) === false) {
            $this->errors[] = 'Start date is not a valid date.';
        }

        if (!self::isValidYear((int) $startDateTime->format('Y'))) {
            $this->errors[] = 'Start date year is not valid.';
        }

        // $currentDate = new DateTime();
        // if ($startDateTime < $currentDate) {
        //     $this->errors[] = 'Start date cannot be in the past.';
        // }
    }

    /**
     * Validate completion date and time
     */
    public function validateCompletionDateTime(?DateTime $completionDateTime, ?DateTime $startDateTime = null): void
    {
        if ($completionDateTime === null) {
            $this->errors[] = 'Invalid completion date and time.';
            return;
        }

        if (checkdate((int) $completionDateTime->format('m'), (int) $completionDateTime->format('d'), (int) $completionDateTime->format('Y')) === false) {
            $this->errors[] = 'Completion date is not a valid date.';
        }

        if (!self::isValidYear((int) $completionDateTime->format('Y'))) {
            $this->errors[] = 'Completion date year is not valid.';
        }

        if ($startDateTime !== null && $completionDateTime <= $startDateTime) {
            $this->errors[] = 'Completion date must be after the start date.';
        }
    }

    /**
     * Validate that start and completion date-times are within given bounds
     * 
     * @param DateTime|null $startDateTime Start date-time to validate
     * @param DateTime|null $completionDateTime Completion date-time to validate
     * @param DateTime|null $boundStartDateTime Lower bound start date-time
     * @param DateTime|null $boundCompletionDateTime Upper bound completion date-time
     * @param string $context Context for error messages (e.g., 'Project', 'Phase')
     */
    public function validateDateBounds(
        ?DateTime $startDateTime,
        ?DateTime $completionDateTime,
        ?DateTime $boundStartDateTime,
        ?DateTime $boundCompletionDateTime,
        string $context = 'Project'
    ): void {
        $context = ucwords(trim($context));

        if ($startDateTime === null || $completionDateTime === null) {
            $this->errors[] = 'Start date and completion date are required.';
            return;
        }

        if ($boundStartDateTime === null || $boundCompletionDateTime === null) {
            $this->errors[] = $context . ' start date and completion date are required.';
            return;
        }

        if ($startDateTime < $boundStartDateTime) {
            $this->errors[] = 'Start date cannot be before ' . $context . ' start date.';
        }

        if ($startDateTime > $boundCompletionDateTime) {
            $this->errors[] = 'Start date cannot be after ' . $context . ' completion date.';
        }

        if ($completionDateTime > $boundCompletionDateTime) {
            $this->errors[] = 'Completion date cannot be after ' . $context . ' completion date.';
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
