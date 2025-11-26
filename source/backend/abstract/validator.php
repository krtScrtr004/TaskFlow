<?php

namespace App\Abstract;

abstract class Validator {
    protected array $errors = [];

    /**
     * Determines if the provided string contains a run of three or more consecutive special characters.
     *
     * The method checks the input against a regular expression that matches sequences (length >= 3)
     * made up of any of these characters: $ % # & _ ! @ ' . * ( ) [ ] { } + -
     *
     * Useful for input validation to reject or flag strings that include excessive consecutive punctuation
     * or symbol characters.
     *
     * @param string $input The input string to examine.
     *
     * @return bool True if the input contains three or more consecutive special characters; otherwise false.
     */
    protected function hasConsecutiveSpecialChars(string $input): bool {
        return preg_match('/[$%#&_!@\'\.\*\(\)\[\]\{\}\+\-]{3,}/', $input) === 1;
    }

    /**
     * Determines if a year is within the allowed range.
     *
     * A year is considered valid when it is >= 1900 and <= (current year + 100).
     * The upper bound is computed at runtime using (int)date('Y') + 100.
     *
     * @param int $year Year to validate
     * @return bool True if the year is within the allowed range, false otherwise
     */
    protected function isValidYear(int $year): bool {
        return $year >= 1900 && $year <= (int)date('Y') + 100;
    }

    /**
     * Adds a validation error to the validator's internal errors collection.
     *
     * This method stores a human-readable error message keyed by an identifier:
     * - The $key is used as the index in the internal errors array.
     * - The $message should describe the validation failure for that key.
     * - If an error already exists for the given key, it will be overwritten.
     *
     * @param string $key Identifier for the error (e.g. field name or rule)
     * @param string $message Human-readable error message describing the validation failure
     *
     * @return void
     */
    public function addError(string $key, string $message): void {
        $this->errors[$key] = $message;
    }

    /**
     * Indicates whether the validator has any recorded errors.
     *
     * Performs a simple emptiness check on the validator's internal $errors container
     * and returns true if it contains one or more entries. This method does not
     * modify the errors or perform additional validation — it only reports presence.
     *
     * The $errors container may hold:
     *  - string messages
     *  - arrays keyed by field names with message lists
     *  - objects or structures describing validation failure details
     *
     * @return bool True when one or more validation errors are present, false otherwise
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    /**
     * Returns the validation errors collected by the validator.
     *
     * This method provides a snapshot of the validator's current error state
     * from the last validation run. It does not modify the internal state.
     *
     * The returned array commonly uses one of these shapes:
     * - Associative mapping of fieldName => array of error messages
     * - Numeric-indexed list of generic error messages not tied to a specific field
     *
     * Examples:
     * - ['email' => ['Email is required', 'Email is invalid']]
     * - ['Password is too short', 'Unexpected field value']
     *
     * @return array Validation errors structured as:
     *      - fieldName: string => array<int,string>  List of error messages for that field
     *      - (int) => string                        General error messages not tied to a field
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Returns the first validation error message, or null if there are no errors.
     *
     * This method inspects the validator's internal $errors array and retrieves its first element:
     * - If $errors is not empty, the value of the first element is returned (not the key).
     * - If $errors is empty, null is returned.
     *
     * Note: This implementation uses reset($this->errors) to obtain the first element, which may advance/reset the array's internal pointer.
     *
     * @return string|null First error message from the errors array, or null when there are no errors.
     */
    public function getFirstError(): ?string {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}