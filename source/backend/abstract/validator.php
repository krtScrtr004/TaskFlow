<?php

namespace App\Abstract;

abstract class Validator {
    protected array $errors = [];

    protected function isValidYear(int $year): bool {
        return $year >= 1900 && $year <= (int)date('Y');
    }

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