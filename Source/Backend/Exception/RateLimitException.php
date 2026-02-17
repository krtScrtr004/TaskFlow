<?php

namespace App\Exception;

use App\Abstract\CustomException;

class RateLimitException extends CustomException
{
    /**
     * Constructs a RateLimitException.
     *
     * Creates an exception representing an authorization/permission failure.
     * - Uses the provided message or the default: "Rate limit exceeded. Please try again later."
     * - Sets the internal application error code to 4029 via the parent constructor
     * - Sends an HTTP 429 (Too Many Requests) response code
     *
     * @param string $message Optional custom error message
     *
     * @return void
     */
    public function __construct(string $message = 'Rate limit exceeded. Please try again later.',)
    {
        parent::__construct($message, 4029);
        http_response_code(429);
    }
}