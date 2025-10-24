<?php

namespace App\Exception;

use App\Abstract\CustomException;

class ForbiddenException extends CustomException
{
    public function __construct(string $message = 'Forbidden',)
    {
        parent::__construct($message, 4003);
        http_response_code(403);
    }
}