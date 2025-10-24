<?php

namespace App\Exception;

use App\Abstract\CustomException;

class ValidationException extends CustomException
{
    public function __construct(string $message = 'Validation Error', array $errors = [])
    {
        parent::__construct($message, 1000);
        $this->errors = $errors;
    }
}