<?php

namespace App\Endpoint;

use App\Abstract\Endpoint;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Service\AboutUsService;
use App\Utility\ResponseExceptionHandler;
use App\Validator\UserValidator;
use Throwable;

class AboutUsEndpoint extends Endpoint
{
    private AboutUsService $aboutUsService;

    private function __construct()
    {
    }

    /**
     * Handles sending a "concern" from the About Us endpoint.
     *
     * This method:
     * - Protects the request against CSRF attacks via Csrf::protect()
     * - Applies rate limiting using RateLimiter middleware (5 requests per minute)
     * - Decodes the request body from php://input into an associative array
     * - Validates required fields (fullName, email, message) using UserValidator
     * - Sanitizes validated input via sanitizeData()
     * - Sends the concern email using AboutUsService::sendConcernEmail(fullName, email, message)
     * - Returns a success response via Response::success()
     * - Catches any Throwable and delegates error handling to ResponseExceptionHandler::handle()
     *
     * Expected JSON request body keys:
     *      - fullName: string Sender's full name (required)
     *      - email: string Sender's email address (required)
     *      - message: string Message content of the concern (required)
     *
     * Behavior notes:
     * - If decoding the request body fails, a ValidationException is raised and handled.
     * - If validation fails, a ValidationException containing validator errors is raised and handled.
     * - All input is sanitized before being passed to the AboutUsService.
     *
     * @return void
     */
    public static function sendConcern(): void
    {
        try {
            Csrf::protect();

            $instance = new self();
            $instance->rateLimiter->handle(
                $instance->getIpAddress(), 
                $instance->getEndpointName(), 
                ['limit' => 5, 'timeWindow' => 60] // 5 requests per minute
            );

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $validator = new UserValidator();
            $validator->validateMultiple([
                'fullName' => $data['fullName'] ?? '',
                'email' => $data['email'] ?? '',
                'message' => $data['message'] ?? ''
            ]);
            if ($validator->hasErrors()) {
                throw new ValidationException('Validation failed.', $validator->getErrors());
            }

            // Sanitize data
            sanitizeData($data, [
                'fullName',
                'email',
                'message'
            ]);

            // Send email
            $aboutUsService = new AboutUsService();
            $aboutUsService->sendConcernEmail(
                $data['fullName'],
                $data['email'],
                $data['message']
            );

            Response::success([], 'Concern sent successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Send Concern Failed.', $e);
        }
    }

    /**
     * Not implemented (No use case)
     */
    public static function getById(array $args = []): void
    {
    }

    /**
     * Not implemented (No use case)
     */
    public static function getByKey(array $args = []): void
    {
    }

    /**
     * Not implemented (No use case)
     */
    public static function create(array $args = []): void
    {
    }

    /**
     * Not implemented (No use case)
     */
    public static function edit(array $args = []): void
    {
    }

    /**
     * Not implemented (No use case)
     */
    public static function delete(array $args = []): void
    {
    }

}