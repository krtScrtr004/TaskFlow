<?php

namespace App\Exception;

use App\Abstract\CustomException;

class NotFoundException extends CustomException
{
    public function __construct(string $message = 'Not Found',)
    {
        parent::__construct($message, 4004);
        http_response_code(404);
    }
}