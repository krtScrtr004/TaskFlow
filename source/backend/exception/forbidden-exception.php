<?php

namespace App\Exception;

use App\Abstract\CustomException;

class ForbiddenException extends CustomException
{
    /**
     * Constructs a ForbiddenException.
     *
     * Creates an exception representing an authorization/permission failure.
     * - Uses the provided message or the default: "You do not have permission to do this action."
     * - Sets the internal application error code to 4003 via the parent constructor
     * - Sends an HTTP 403 (Forbidden) response code
     *
     * @param string $message Optional custom error message
     *
     * @return void
     */
    public function __construct(string $message = 'You do not have permission to do this action.',)
    {
        parent::__construct($message, 4003);
        http_response_code(403);
    }
}