<?php

namespace App\Endpoint;

use App\Auth\SessionAuth;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Service\AboutUsService;
use App\Utility\ResponseExceptionHandler;
use App\Validator\UserValidator;
use Throwable;

class AboutUsEndpoint
{
    private AboutUsService $aboutUsService;

    private function __construct()
    {
    }

    public static function sendConcern(): void {
        try {
            Csrf::protect();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $validator = new UserValidator();
            $validator->validateMultiple([
                'fullName'  => $data['fullName'] ?? '',
                'email'     => $data['email'] ?? '',
                'message'   => $data['message'] ?? ''
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
}