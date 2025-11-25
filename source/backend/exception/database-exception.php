<?php

namespace App\Exception;

use App\Abstract\CustomException;

class DatabaseException extends CustomException
{    
    /**
     * Constructs a DatabaseException with a descriptive message and a fixed error code.
     *
     * This constructor initializes the exception with:
     * - A human-readable message describing the database error (defaults to 'A database error occurred.')
     * - A fixed internal error code of 5000 passed to the parent exception
     *
     * @param string $message Optional descriptive error message (default: 'A database error occurred.')
     *
     * @return self New DatabaseException instance
     */
    public function __construct(string $message = 'A database error occurred.')
    {
        parent::__construct($message, 5000);
    }
}