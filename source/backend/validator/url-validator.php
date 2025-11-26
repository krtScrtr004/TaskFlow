<?php

namespace App\Validator;

use App\Abstract\Validator;

class UrlValidator extends Validator
{
    /**
     * Validate a URL and record any validation errors on the current validator instance.
     *
     * This method performs the following checks in order:
     * - If the value is null or empty after trimming, records "URL cannot be empty." and returns immediately.
     * - Ensures the length (measured with mb_strlen) is between the URI_MIN and URI_MAX constants; if not,
     *   records "URL must be between {URI_MIN} and {URI_MAX} characters long.".
     * - Validates the URL format using filter_var(..., FILTER_VALIDATE_URL); if invalid, records "Invalid URL format.".
     *
     * Notes:
     * - Errors are appended to $this->errors[]; the method does not throw exceptions.
     * - Length checks use multibyte-safe mb_strlen.
     *
     * @param string|null $url The URL to validate; may be null
     *
     * @return void
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
