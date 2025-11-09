<?php

namespace App\Validator;

use App\Abstract\Validator;

class UrlValidator extends Validator
{
    /**
     * Validate URL
     */
    public function validateUrl(?string $url): void
    {
        if ($url === null || trim($url) === '') {
            $this->errors[] = 'URL cannot be empty.';
            return;
        }

        if (mb_strlen($url) < URI_MIN || mb_strlen($url) > URI_MAX) {
            $this->errors[] = 'URL must be between ' . URI_MIN . ' and ' . URI_MAX . ' characters long.';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[] = 'Invalid URL format.';
        }
    }
}
