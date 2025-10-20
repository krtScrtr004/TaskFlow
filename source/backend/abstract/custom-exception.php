<?php

namespace App\Abstract;

use Exception;

abstract class CustomException extends Exception
{
    private array $errors = [];

    public function __construct($message = "Validation Error", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function count(): int
    {
        return count($this->errors);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

}