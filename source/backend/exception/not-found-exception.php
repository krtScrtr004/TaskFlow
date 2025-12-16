<?php

namespace App\Exception;

use App\Abstract\CustomException;

class NotFoundException extends CustomException
{
    /**
     * Constructs a NotFoundException with an optional message.
     *
     * This constructor:
     * - Accepts an optional human-readable error message (default: 'Not Found').
     * - Initializes the parent exception with a fixed application error code (4004).
     * - Sends an HTTP 404 status code to the client as a side effect via http_response_code().
     *
     * @param string $message Optional error message describing the not-found condition (defaults to 'Not Found')
     *
     * @return void
     */
    public function __construct(string $message = 'Not Found',)
    {
        parent::__construct($message, 4004);
        http_response_code(404);
    }
}