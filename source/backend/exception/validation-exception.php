<?php

namespace App\Exception;

use App\Abstract\CustomException;

class ValidationException extends CustomException {
    public function __construct($message = 'Validation Error', array $errors = [], $code = 0, \Exception|null $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }
}