<?php

namespace App\Abstract;

abstract class Validator
{
    protected array $errors = [];

    /**
     * Validates a name string and records validation errors.
     *
     * This method trims the provided name and performs a series of validations:
     * length checks (using $options['min'] and $options['max']), detection of
     * consecutive special characters, allowed-character validation via a regular
     * expression, and any additional custom checks supplied in $options.
     *
     * Behavior and side effects:
     * - Trims whitespace from the input name.
     * - Ensures the name is present and its length is between $options['min'] and
     *   $options['max']; if not, appends an error message to $this->errors.
     * - Uses $this->hasConsecutiveSpecialChars($name) to detect consecutive special
     *   characters; if found, appends an error to $this->errors.
     * - Validates allowed characters (letters, spaces, apostrophe, hyphen, dot)
     *   using a regular expression that references the NAME_MIN and NAME_MAX
     *   constants; if the regex fails, appends an error to $this->errors.
     * - Iterates over callables in $options['additionalChecks'], invoking each as
     *   $error = $check($name); if a callable returns a non-null string, that
     *   string is appended to $this->errors.
     * - All validation results are recorded by appending messages to $this->errors.
     * - This method does not throw exceptions for validation failures and returns
     *   no value.
     *
     * @param string|null $name The name to validate (may be null).
     * @param array{
     *      min: int,
     *      max: int,
     *      fieldLabel: string,
     *      additionalChecks: array<callable(string): (string|null)>
     * } $options Validation options with keys:
     *   - min: Minimum allowed length (default: NAME_MIN).
     *   - max: Maximum allowed length (default: NAME_MAX).
     *   - fieldLabel: Label used in error messages (default: 'Name').
     *   - additionalChecks: Array of callables (function(string): ?string)
     *     that receive the trimmed name and return an error message string or null.
     *
     * @return void
     */
    protected function iValidateName(
        ?string $name,
        array $options = [
            'min' => NAME_MIN,
            'max' => NAME_MAX,
            'fieldLabel' => 'Name',
            'additionalChecks' => []
        ]
    ): void {
        $min = $options['min'] ?? NAME_MIN;
        $max = $options['max'] ?? NAME_MAX;
        $fieldLabel = $options['fieldLabel'] ?? 'Name';

        $name = trim($name);
        if (strlen($name) < $min || strlen($name) > $max)
            $this->errors[] = "{$fieldLabel} must be between {$min} and {$max} characters.";

        if (!preg_match('/^[\w\p{L}\s\'\-.]+$/u', $name))
            $this->errors[] = "{$fieldLabel} must only contain letters, spaces, and common punctuation.";

        if ($this->hasConsecutiveSpecialChars($name))
            $this->errors[] = "{$fieldLabel} must not contain consecutive special characters.";

        // Run any additional custom checks provided in options
        foreach ($options['additionalChecks'] as $check) {
            $error = $check($name);
            if ($error !== null) $this->errors[] = $error;
        }
    }

    /**
     * Validates a long text message (note) and records validation errors.
     *
     * This method trims the provided note and, if non-empty, enforces length constraints,
     * checks for consecutive special characters, and runs any additional custom checks
     * supplied via the options array. Validation failures are appended to $this->errors.
     *
     * Behavior and side effects:
     * - Trims the $note with trim() and returns early (no errors) if the resulting string is empty.
     * - Validates that the length of $note is between $options['min'] and $options['max']; on failure
     *   appends "{fieldLabel} must be between {min} and {max} characters." to $this->errors.
     * - Uses $this->hasConsecutiveSpecialChars($note) to detect consecutive special characters; on
     *   detection appends "{fieldLabel} must not contain consecutive special characters." to $this->errors.
     * - Executes each callable in $options['additionalChecks'] with the note as argument. If a callable
     *   returns a non-null value (expected to be an error message), that value is appended to $this->errors.
     * - Mutates $this->errors; does not throw exceptions or perform other external side effects.
     *
     * @param string|null $note The text to validate (may be null).
     * @param array{
     *      min: int,
     *      max: int,
     *      fieldLabel: string,
     *      additionalChecks: array<callable(string): (string|null)>
     * } $options Validation options:
     *     - min: minimum allowed length (default LONG_TEXT_MIN)
     *     - max: maximum allowed length (default LONG_TEXT_MAX)
     *     - fieldLabel: human-readable field name used in error messages (default 'Note')
     *     - additionalChecks: array of callables receiving the note and returning an error string or null
     *
     * @return void
     */
    public function iValidateLongMessage(
        ?string $note,
        array $options = [
            'min' => LONG_TEXT_MIN,
            'max' => LONG_TEXT_MAX,
            'fieldLabel' => 'Note',
            'additionalChecks' => []

        ]
    ): void {
        $note = trim($note);
        $min = $options['min'] ?? LONG_TEXT_MIN;
        $max = $options['max'] ?? LONG_TEXT_MAX;
        $fieldLabel = $options['fieldLabel'] ?? 'Note';

        if (!$note) return;

        if (strlen($note) < $min || strlen($note) > $max)
            $this->errors[] = "{$fieldLabel} must be between {$min} and {$max} characters.";

        if ($this->hasConsecutiveSpecialChars($note))
            $this->errors[] = "{$fieldLabel} must not contain consecutive special characters.";

        // Run any additional custom checks provided in options
        foreach ($options['additionalChecks'] as $check) {
            $error = $check($note);
            if ($error !== null) $this->errors[] = $error;
        }
    }

    /**
     * Validate a default rate value and record any validation errors.
     *
     * This method checks that the provided nullable float $rate falls within the inclusive range
     * defined by $options['min'] and $options['max']. If the value is outside the range, an error
     * message is appended to $this->errors using the provided $options['fieldLabel'] (defaults to
     * "Default Rate"). After the range check, any callables listed in $options['additionalChecks']
     * are invoked with the $rate; if a callable returns a non-null string, that string is appended
     * to $this->errors as an additional validation error.
     *
     * Behavior and side effects:
     * - Accepts a nullable float ($rate) and an $options array controlling validation parameters.
     * - Default options:
     *     - 'min' => DEFAULT_RATE_MIN (float)
     *     - 'max' => DEFAULT_RATE_MAX (float)
     *     - 'fieldLabel' => 'Default Rate' (string)
     *     - 'additionalChecks' => [] (array of callables)
     * - If $rate < $options['min'] || $rate > $options['max'], appends
     *   "{$fieldLabel} must be between {$min} and {$max}." to $this->errors.
     * - Iterates and executes each callable in 'additionalChecks' with the signature
     *   callable(?float): ?string; if the callable returns a non-null string, that string is
     *   appended to $this->errors.
     * - Mutates $this->errors by appending error messages; does not throw exceptions.
     * - Relies on PHP's numeric comparison semantics when $rate is null (i.e., null may be
     *   compared against numeric bounds according to PHP rules).
     *
     * @param float|null $rate The rate value to validate (may be null).
     * @param array{
     *      min: float,
     *      max: float,
     *      fieldLabel: string,
     *      additionalChecks: array<callable(?float): (string|null)>
     * } $options Validation options. Expected keys:
     *     - min: minimum allowed value (inclusive).
     *     - max: maximum allowed value (inclusive).
     *     - fieldLabel: label used in generated error messages.
     *     - additionalChecks: array of callables with signature
     *     callable(?float): ?string; each callable should return a string error message
     *     when validation fails or null when it passes.
     *
     * @return void
     */
    public function iValidateDefaultRate(
        ?float $rate,
        array $options = [
            'min' => DEFAULT_RATE_MIN,
            'max' => DEFAULT_RATE_MAX,
            'fieldLabel' => 'Default Rate',
            'additionalChecks' => []
        ]
    ): void {
        $min = $options['min'] ?? DEFAULT_RATE_MIN;
        $max = $options['max'] ?? DEFAULT_RATE_MAX;
        $fieldLabel = $options['fieldLabel'] ?? 'Default rate';

        if ($rate < $min || $rate > $max)
            $this->errors[] = "{$fieldLabel} must be between {$min} and {$max}.";

        // Run any additional custom checks provided in options
        foreach ($options['additionalChecks'] as $check) {
            $error = $check($rate);
            if ($error !== null) $this->errors[] = $error;
        }
    }

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
    protected function hasConsecutiveSpecialChars(string $input): bool
    {
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
    protected function isValidYear(int $year): bool
    {
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
    public function addError(string $key, string $message): void
    {
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
    public function hasErrors(): bool
    {
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
    public function getErrors(): array
    {
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
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
