<?php

namespace App\Abstract;

use Exception;

abstract class CustomException extends Exception
{
    protected array $errors = [];

    /**
     * Constructs a Custom Error exception.
     *
     * Initializes the exception with a human-readable message, an integer code,
     * and an optional previous exception for exception chaining. Delegates to the
     * parent Exception constructor to preserve standard exception behavior.
     *
     * @param string $message Human-readable error message. Defaults to "Custom Error".
     * @param int $code Numeric error code. Defaults to 0.
     * @param Exception|null $previous Previous exception used for exception chaining, or null if none.
     */
    public function __construct($message = "Custom Error", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the number of errors stored in this exception.
     *
     * This method provides the count of entries in the internal errors container.
     * It acts as a thin wrapper around PHP's count() for the $errors property,
     * indicating how many error items are currently recorded on the exception.
     *
     * @return int Number of errors contained in the exception
     */
    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Checks whether the exception contains any recorded errors.
     *
     * This method inspects the internal error storage and returns true when one or
     * more error entries are present. Use this to determine if additional error
     * processing or reporting is required before proceeding.
     *
     * @return bool True if there are any errors recorded, false otherwise
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns the collected errors for this exception instance.
     *
     * The errors array represents validation or runtime errors captured by the exception.
     * Entries are keyed by an error identifier or field name and the values can be:
     * - string: a single error message
     * - array: one or more error messages for the given key (indexed or associative)
     *
     * Example structures:
     * - ['general' => 'An unexpected error occurred']
     * - ['email' => ['Invalid format', 'Already taken'], 'password' => 'Too short']
     *
     * @return array<string, string|array> Associative array of errors where keys are
     *      error identifiers or field names and values are either a single string message
     *      or an array of messages describing the error(s).
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}