<?php 

namespace App\Utility;

use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\RateLimitException;
use App\Exception\ValidationException;
use App\Middleware\Response;
use Throwable;

class ResponseExceptionHandler
{
    /**
     * Handles exceptions and sends an appropriate error response via Response::error().
     *
     * This method maps specific exception types to HTTP status codes and response payloads:
     * - ValidationException: uses $exception->getErrors() and responds with 422 Unprocessable Entity
     * - NotFoundException: uses $exception->getMessage() and responds with 404 Not Found
     * - ForbiddenException: uses $exception->getMessage() and responds with 403 Forbidden
     * - Any other Throwable: returns a generic error message with 500 Internal Server Error
     *
     * @param string $title Title for the error response
     * @param \Throwable $exception The exception to handle. Expected concrete types:
     *      - ValidationException (provides getErrors(): array)
     *      - NotFoundException (provides getMessage(): string)
     *      - ForbiddenException (provides getMessage(): string)
     *      - any other Throwable
     *
     * @return void
     */
    public static function handle(string $title, Throwable $exception): void
    {
        if ($exception instanceof ValidationException) {
            Response::error($title, $exception->getErrors(), 422);
        } elseif ($exception instanceof NotFoundException) {
            Response::error($title, [$exception->getMessage()], 404);
        } elseif ($exception instanceof ForbiddenException) {
            Response::error($title, [$exception->getMessage()], 403);
        } elseif ($exception instanceof RateLimitException) {
            Response::error($title, [$exception->getMessage()], 429);
        } else {
            Response::error($title, ['An unexpected error occurred. Please try again later.'], 500);
        }
    }
}