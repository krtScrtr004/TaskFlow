<?php 

namespace App\Utility;

use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Middleware\Response;
use Throwable;

class ResponseExceptionHandler
{
    public static function handle(string $title, Throwable $exception): void
    {
        if ($exception instanceof ValidationException) {
            Response::error($title, $exception->getErrors(), 422);
        } elseif ($exception instanceof NotFoundException) {
            Response::error($title, [$exception->getMessage()], 404);
        } elseif ($exception instanceof ForbiddenException) {
            Response::error($title, [$exception->getMessage()], 403);
        } else {
            Response::error($title, ['An unexpected error occurred. Please try again later.'], 500);
        }
    }
}