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
     * Validate that phase/task dates are within project date bounds
     * 
     * @param DateTime|null $startDateTime The phase/task start date
     * @param DateTime|null $completionDateTime The phase/task completion date
     * @param DateTime|null $projectStartDateTime The project start date
     * @param DateTime|null $projectCompletionDateTime The project completion date
     * @return void
     */
    public function validateDateBounds(
        ?DateTime $startDateTime,
        ?DateTime $completionDateTime,
        ?DateTime $projectStartDateTime,
        ?DateTime $projectCompletionDateTime
    ): void {
        if ($startDateTime === null || $completionDateTime === null) {
            $this->errors[] = 'Start date and completion date are required.';
            return;
        }

        if ($projectStartDateTime === null || $projectCompletionDateTime === null) {
            $this->errors[] = 'Project start date and completion date are required.';
            return;
        }

        if ($startDateTime < $projectStartDateTime) {
            $this->errors[] = 'Start date cannot be before project start date.';
        }

        if ($startDateTime > $projectCompletionDateTime) {
            $this->errors[] = 'Start date cannot be after project completion date.';
        }

        if ($completionDateTime > $projectCompletionDateTime) {
            $this->errors[] = 'Completion date cannot be after project completion date.';
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
