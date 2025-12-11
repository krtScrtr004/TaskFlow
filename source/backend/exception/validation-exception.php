<?php

namespace App\Exception;

use App\Abstract\CustomException;

class ValidationException extends CustomException
{
    /**
     * Constructs a ValidationException.
     *
     * This constructor initializes the exception with a human-readable message and
     * an array of validation errors, then delegates to the base Exception with a
     * fixed exception code (1000).
     *
     * Behavior:
     * - Sets the exception message (defaults to 'Validation Error').
     * - Calls parent::__construct($message, 1000) to set the message and a fixed code.
     * - Stores the provided $errors into $this->errors for later retrieval.
     *
     * The $errors array is intended to carry validation failure details and commonly
     * follows one of these shapes:
     * - ['fieldName' => 'Error message']
     * - ['fieldName' => ['Error message 1', 'Error message 2']]
     * - ['fieldName' => ['subField' => ['...']]] (nested / structured errors)
     * - ['_global' => 'General error not tied to a specific field'] (optional)
     *
     * @param string $message Human-readable exception message (default: 'Validation Error')
     * @param array $errors Associative array of validation errors. Keys are typically
     *      field names (or '_global' for non-field-specific errors) and values are:
     *      - string A single error message
     *      - array  One or more error messages or nested error structures
     *
     * @return self A new ValidationException instance initialized with the message and errors
     */
    public function __construct(string $message = 'Validation Error', array $errors = [])
    {
        parent::__construct($message, 1000);
        $this->errors = $errors;
    }
}