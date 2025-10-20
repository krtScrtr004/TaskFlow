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
            $this->errors['url'] = 'URL cannot be empty.';
            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors['url'] = 'Invalid URL format.';
        }
    }
}
