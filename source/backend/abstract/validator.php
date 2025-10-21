<?php

namespace App\Abstract;

abstract class Validator {
    protected array $errors = [];

    public function addError(string $key, string $message): void {
        $this->errors[$key] = $message;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstError(): ?string {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}