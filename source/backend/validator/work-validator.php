<?php

namespace App\Validator;

use App\Abstract\Validator;
use DateTime;

class WorkValidator extends Validator
{
    /**
     * Validates a name and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Treats a null value or a string that is empty after trimming as invalid.
     * - Ensures the trimmed name length is between the constants NAME_MIN and NAME_MAX.
     * - Checks for the presence of three or more consecutive special characters using hasConsecutiveSpecialChars().
     *
     * On failure, the corresponding error messages are added to $this->errors:
     * - "Name must be between {NAME_MIN} and {NAME_MAX} characters long." when the length check fails or name is null/empty.
     * - "Name contains three or more consecutive special characters." when the consecutive-special-character check fails.
     *
     * @param string|null $name The name to validate. May be null.
     *
     * @return void
     */
    public function validateName(?string $name): void
    {
        if ($name === null || strlen(trim($name)) < NAME_MIN || strlen(trim($name)) > NAME_MAX) {
            $this->errors[] = 'Name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if ($this->hasConsecutiveSpecialChars($name)) {
            $this->errors[] = 'Name contains three or more consecutive special characters.';
        }
    }

    /**
     * Validates a description string and records validation errors.
     *
     * This method performs validation only when a non-null description is provided:
     * - Trims surrounding whitespace and ensures the resulting length is between LONG_TEXT_MIN and LONG_TEXT_MAX.
     * - Verifies the description does not contain three or more consecutive special characters by delegating to hasConsecutiveSpecialChars().
     *
     * @param string|null $description The description to validate; pass null to skip validation.
     *
     * @return void
     *
     * Side effects:
     * - If the length check fails, an error message is appended to $this->errors:
     *     "Description must be between {LONG_TEXT_MIN} and {LONG_TEXT_MAX} characters long."
     * - If the consecutive-special-character check fails, an error message is appended to $this->errors:
     *     "Description contains three or more consecutive special characters."
     *
     * Notes:
     * - Length checks use strlen() on the trimmed string.
     * - LONG_TEXT_MIN and LONG_TEXT_MAX are expected to be defined constants.
     * - hasConsecutiveSpecialChars() is a class helper used to detect consecutive special characters.
     */
    public function validateDescription(?string $description): void
    {
        if ($description !== null && (strlen(trim($description)) < LONG_TEXT_MIN || strlen(trim($description)) > LONG_TEXT_MAX)) {
            $this->errors[] = 'Description must be between ' . LONG_TEXT_MIN . ' and ' . LONG_TEXT_MAX . ' characters long.';
        }

        if ($description !== null && $this->hasConsecutiveSpecialChars($description)) {
            $this->errors[] = 'Description contains three or more consecutive special characters.';
        }
    }

    /**
     * Validates a budget value and records an error if it is invalid.
     *
     * This method performs the following checks:
     * - Ensures the value is not null
     * - Ensures the value is numeric (int, float, or numeric string)
     * - Ensures the numeric value is within the inclusive range defined by BUDGET_MIN and BUDGET_MAX
     *
     * If any check fails, an error message is appended to $this->errors describing the allowed range.
     *
     * @param int|float|string|null $budget The budget value to validate. Accepts numeric types or numeric strings; null is considered invalid.
     * @return void
     */
    public function validateBudget($budget): void
    {
        if ($budget === null || !is_numeric($budget) || $budget < BUDGET_MIN || $budget > BUDGET_MAX) {
            $this->errors[] = 'Budget must be a number between ' . BUDGET_MIN . ' and ' . BUDGET_MAX . '.';
        }
    }

    /**
     * Validates a start date/time and appends any validation errors to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures a DateTime value is provided; if null, appends 'Invalid start date and time.'
     * - Uses PHP's checkdate() on the month, day and year extracted from the DateTime to ensure the date is a real calendar date;
     *   if not valid, appends 'Start date is not a valid date.'
     * - Validates the year component using self::isValidYear(); if the year is not acceptable, appends 'Start date year is not valid.'
     *
     * Note: Errors are collected as plain strings in $this->errors and no exception is thrown by this method.
     *
     * @param DateTime|null $startDateTime DateTime instance representing the start date/time, or null if not provided
     * @return void
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
    }

    /**
     * Validates a completion DateTime ensuring it is present, represents a valid calendar date,
     * its year is acceptable, and (when a start date is provided) that it occurs after the start date.
     *
     * This method performs the following checks and records validation errors in $this->errors:
     * - Verifies that $completionDateTime is not null.
     * - Uses checkdate(month, day, year) to ensure the date components form a valid calendar date.
     * - Uses self::isValidYear(int $year) to ensure the year component is within allowed bounds.
     * - If $startDateTime is provided, ensures $completionDateTime is strictly greater than $startDateTime.
     *
     * Possible error messages appended to $this->errors:
     *  - 'Invalid completion date and time.'         when $completionDateTime is null
     *  - 'Completion date is not a valid date.'      when checkdate(...) fails
     *  - 'Completion date year is not valid.'        when self::isValidYear(...) returns false
     *  - 'Completion date must be after the start date.' when $completionDateTime <= $startDateTime
     *
     * @param \DateTime|null $completionDateTime The completion date/time to validate.
     * @param \DateTime|null $startDateTime Optional start date/time to compare against; if provided,
     *                                      the completion date/time must be strictly after this value.
     *
     * @return void
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
     * Validates that a pair of start and completion DateTime values fall within given bounding dates.
     *
     * This method performs the following validations and appends human-readable error messages to $this->errors:
     * - Ensures both $startDateTime and $completionDateTime are provided; otherwise records a generic required-fields error.
     * - Ensures both $boundStartDateTime and $boundCompletionDateTime are provided; otherwise records a context-specific required-fields error.
     * - Ensures the start date is not earlier than the bound start date.
     * - Ensures the start date is not later than the bound completion date.
     * - Ensures the completion date is not later than the bound completion date.
     *
     * Error messages include the formatted bound date (Y-m-d) and prefix the bound-related messages with the provided context
     * (default "Project", normalized via ucwords and trim).
     *
     * @param DateTime|null $startDateTime The start date/time to validate.
     * @param DateTime|null $completionDateTime The completion date/time to validate.
     * @param DateTime|null $boundStartDateTime The earliest allowed start date/time (boundary).
     * @param DateTime|null $boundCompletionDateTime The latest allowed completion date/time (boundary).
     * @param string $context Optional context name used in messages (default "Project"). It will be trimmed and converted with ucwords.
     *
     * @return void
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
            $this->errors[] = 'Start date cannot be before ' . $context . ' start date (' . formatDateTime($boundStartDateTime, 'Y-m-d') . ').';
        }

        if ($startDateTime > $boundCompletionDateTime) {
            $this->errors[] = 'Start date cannot be after ' . $context . ' completion date (' . formatDateTime($boundCompletionDateTime, 'Y-m-d') . ').';
        }

        if ($completionDateTime > $boundCompletionDateTime) {
            $this->errors[] = 'Completion date cannot be after ' . $context . ' completion date (' . formatDateTime($boundCompletionDateTime, 'Y-m-d') . ').';
        }
    }

    // ------------------------------------------------------------------------------------------------------------------------------ //

    /**
     * Validates multiple work-related fields provided in an associative array.
     *
     * Only fields that are present in the input array are validated. This method:
     * - Trims and validates the 'name' when present.
     * - Trims and validates the 'description' when present.
     * - Validates the 'budget' when present (expects a numeric/parsable amount).
     * - Validates the 'startDateTime' when present (expects a date/time string or DateTime-like value).
     * - Validates the 'completionDateTime' when present and, if startDateTime is provided, validates that the
     *   completion date/time is consistent relative to the start date/time.
     *
     * @param array $data Associative array containing work data with the following optional keys:
     *      - name: string|null Name of the work (trimmed before validation)
     *      - description: string|null Description of the work (trimmed before validation)
     *      - budget: int|float|string|null Budget value (numeric or numeric-string)
     *      - startDateTime: string|\DateTime|null Start date/time of the work
     *      - completionDateTime: string|\DateTime|null Completion date/time of the work (may be compared to startDateTime)
     *
     * @throws \InvalidArgumentException If any provided field fails validation
     *
     * @return void
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
