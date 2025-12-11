<?php

/**
 * Retrieves the value of an HTTP request header by name.
 *
 * This function performs a case-insensitive lookup for the specified header.
 * - If the getallheaders() function exists, it uses it and matches header names case-insensitively.
 * - Otherwise it falls back to the $_SERVER superglobal and synthesizes the header key by:
 *     - replacing '-' with '_'
 *     - uppercasing the name
 *     - prefixing with 'HTTP_'
 *
 * @param string $name Header name to retrieve (e.g. 'Content-Type', 'X-Requested-With'). Lookup is case-insensitive.
 *
 * @return string|null The header value if found, or null if the header is not present.
 */
function getRequestHeader(string $name): ?string {
    // try getallheaders() first (nicest)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            if (strcasecmp($k, $name) === 0) return $v;
        }
    }

    // fallback to $_SERVER superglobal (common in many setups)
    $serverName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
    return $_SERVER[$serverName] ?? null;
}

/**
 * Decode JSON contents from a file and return as an associative array.
 *
 * This function reads the contents of the provided path (file, URL or stream wrapper)
 * using file_get_contents(), decodes the resulting JSON with json_decode(..., true),
 * and returns the resulting associative array.
 *
 * Behavior:
 * - Validates that a non-empty path/identifier was provided.
 * - Uses file_get_contents() to read the data at the given path.
 * - Uses json_decode(..., true) to convert JSON to an associative array.
 * - Treats failures to read or decode as JsonException (invalid JSON or unreadable file).
 *
 * @param string $rawData Path, URL, or stream wrapper pointing to a JSON resource.
 *                        Note: this parameter is treated as a file/stream identifier,
 *                        not a raw JSON string.
 *
 * @return array Decoded JSON as an associative array.
 *
 * @throws ErrorException If no path/identifier ($rawData) is provided or it is empty.
 * @throws JsonException If the file contents cannot be decoded as valid JSON.
 */
function decodeData(String $rawData): array
{
    if (!$rawData)
        throw new ErrorException('No raw JSON is defined.');

    $rawData = file_get_contents($rawData);
    $contents = json_decode($rawData, true);
    if (!$contents)
        throw new JsonException('JSON contents cannot be decoded.');

    return $contents;
}

/**
 * Converts a kebab-case string to camelCase.
 *
 * This function performs the following steps:
 * - Replaces hyphens ('-') with spaces
 * - Uppercases the first letter of each word (ucwords)
 * - Removes all spaces to join the words
 * - Lowercases the first character of the final string (lcfirst)
 *
 * Examples:
 * - "my-example-string" => "myExampleString"
 * - "single" => "single"
 * - "multi-part-name" => "multiPartName"
 *
 * @param string $string Input string in kebab-case (words separated by '-')
 *
 * @return string camelCase version of the provided string
 */
function kebabToCamelCase(string $string): string
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
}

/**
 * Converts a kebab-case string to a sentence-like string by replacing hyphens with spaces.
 *
 * This function replaces every '-' character in the input with a single space, producing a
 * space-separated string suitable for display as a simple sentence fragment. It does not:
 * - change letter casing (no capitalization/decapitalization),
 * - trim leading or trailing whitespace,
 * - collapse multiple consecutive hyphens into a single space beyond the one-to-one replacement.
 *
 * Examples:
 * - "hello-world" -> "hello world"
 * - "-leading-hyphen" -> " leading hyphen"
 * - "multiple--hyphens" -> "multiple  hyphens"
 *
 * @param string $string Kebab-case string (words separated by '-')
 * @return string String with hyphens replaced by spaces
 */
function kebabToSentenceCase(string $string): string
{
    return str_replace('-', ' ', $string);
}

/**
 * Converts a camelCase or PascalCase string into a human-readable sentence.
 *
 * This function handles common camel/Pascal case conversions:
 * - Inserts a space between a lowercase letter followed by an uppercase letter (e.g. "camelCase" -> "camel Case").
 * - Trims surrounding whitespace and normalizes empty or whitespace-only input via trimOrNull.
 * - Capitalizes the first character of the resulting string.
 *
 * @param string $string Input string in camelCase, PascalCase, or already spaced words.
 *
 * @return string Sentence-cased string with the first character capitalized. Returns an empty string if input is empty or only whitespace after trimming.
 */
function camelToSentenceCase(string $string): string
{
    return ucfirst(trimOrNull(preg_replace('/([a-z])([A-Z])/', '$1 $2', $string)));
}

/**
 * Converts a camelCase or PascalCase string to kebab-case.
 *
 * This function inserts a hyphen between a lowercase letter followed by an uppercase letter,
 * then converts the entire result to lowercase. It's intended to transform identifiers
 * commonly written in camelCase or PascalCase into a hyphen-separated kebab-case form.
 *
 * Behavior details:
 * - Inserts hyphens for transitions matching the pattern [a-z][A-Z].
 * - Converts the final string to lowercase.
 * - Does not attempt additional normalization (e.g., trimming, replacing non-alphanumeric characters).
 * - Note: sequences of consecutive uppercase letters (acronyms) may not be split as separate words.
 *
 * Examples:
 * - "myVariableName"  -> "my-variable-name"
 * - "MyClassName"     -> "my-class-name"
 * - "XMLHttpRequest"  -> "xmlhttprequest" (acronym sequence not fully separated)
 *
 * @param string $string Input string in camelCase or PascalCase.
 *
 * @return string Kebab-cased representation of the input (lowercase, words separated by hyphens).
 */
function camelToKebabCase(string $string): string
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
}

/**
 * Converts a sentence-like string into snake_case.
 *
 * This function transforms the provided string by:
 * - Replacing ASCII space characters (' ') with underscores ('_')
 * - Converting the entire string to lowercase using strtolower
 *
 * Behavior notes:
 * - Only the ASCII space character is replaced; other whitespace characters (tabs, newlines)
 *   are left unchanged.
 * - Leading/trailing spaces are not trimmed; consecutive spaces produce consecutive underscores.
 * - Punctuation, special characters and multibyte characters are not normalized or removed.
 *
 * @param string $string Input string (sentence) to be converted
 *
 * @return string The converted string in snake_case (spaces replaced by underscores and lowercased)
 */
function sentenceToSnakeCase(string $string): string 
{
    return strtolower(str_replace(' ', '_', $string));
}

/**
 * Converts a sentence into kebab-case.
 *
 * This function performs the following steps:
 * - Trims surrounding whitespace using trimOrNull()
 * - Replaces ASCII space characters (' ') with hyphens ('-')
 * - Converts the result to lowercase
 * - If the trimmed input is null or empty, the function returns an empty string
 *
 * Notes:
 * - Only literal space characters are replaced; other whitespace (tabs, newlines)
 *   or punctuation are left unchanged.
 * - Multiple consecutive spaces will produce multiple consecutive hyphens.
 * - This function depends on the behavior of trimOrNull() for trimming and empty handling.
 *
 * @param string $string Input sentence to convert to kebab-case
 *
 * @return string Kebab-cased string (lowercase, words separated by '-')
 */
function sentenceToKebabCase(string $string): string
{
    return strtolower(str_replace(' ', '-', trimOrNull($string)));
}

/**
 * Converts a space-separated sentence into camelCase.
 *
 * This function transforms an input sentence by:
 * - Capitalizing the first letter of each word using ucwords
 * - Removing all ASCII space characters
 * - Lowercasing the first character of the resulting string with lcfirst
 * - Preserving non-space punctuation and internal characters of each word
 *
 * Notes:
 * - Only ASCII space characters (' ') are treated as word separators; other whitespace (tabs, newlines) are not specially handled.
 * - The function does not perform additional normalization (e.g., trimming, collapsing other whitespace).
 * - An empty string input yields an empty string.
 *
 * @param string $string Input sentence where words are separated by spaces
 *
 * @return string The input converted to camelCase (first word starts with a lowercase letter, subsequent words capitalized and concatenated)
 */
function sentenceToCamelCase(string $string): string
{
    return lcfirst(str_replace(' ', '', ucwords($string)));
}

/**
 * Converts a monetary amount in major currency units to an integer number of cents.
 *
 * This function performs the following steps:
 * - Multiplies the provided amount by 100 to convert from major units (e.g. dollars) to cents
 * - Rounds the result to the nearest cent using PHP's round() behavior (PHP_ROUND_HALF_UP by default)
 * - Casts the rounded value to int and returns it
 * - Preserves the sign for negative amounts
 *
 * Note: Because the function accepts a float, very large or highly precise values may be subject to floating-point
 * precision limitations. For exact monetary arithmetic consider using integer types from the start or a precise
 * library (BCMath, GMP) or represent amounts as strings.
 *
 * @param float $amount Amount in major currency units (e.g. dollars, euros). May be negative.
 *
 * @return int Amount expressed in cents (rounded to the nearest cent)
 */
function formatBudgetToCents(float $amount): int {
    return (int) round($amount * 100);
}

/**
 * Converts an integer amount in centavos to pesos as a float.
 *
 * This function treats the input as the smallest currency unit (centavos) and
 * converts it to the major currency unit (pesos) by dividing by 100. The
 * returned value preserves fractional pesos. Note that floating-point values
 * can introduce precision issues for financial calculations; for exact decimal
 * arithmetic, prefer using integer cents or a dedicated decimal/bignum library.
 *
 * @param int $amountInCents Amount in centavos (integer)
 *
 * @return float Monetary value in pesos (e.g., 12345 -> 123.45)
 */
function formatBudgetToPesos(int $amountInCents): float {
    return (float) $amountInCents / 100;
}

/**
 * Determines whether the given array is associative.
 *
 * This function checks whether the array's keys form a continuous zero-based
 * integer sequence (0 .. count($array) - 1). If the keys differ from that
 * sequence (e.g. non-integer keys, non-sequential integers, or gaps) the array
 * is considered associative. Empty arrays are treated as non-associative and
 * will return false.
 *
 * Examples:
 *  - [1, 2, 3]         => false (indexed, zero-based sequential keys)
 *  - ['a' => 1, 'b' => 2] => true  (string keys)
 *  - [0 => 'a', 2 => 'b'] => true  (non-sequential integer keys)
 *
 * @param array $array Array to inspect. May contain mixed key types (integers, strings).
 *
 * @return bool True if the array is associative (non-contiguous or non-zero-based integer keys), false otherwise.
 *
 * @note Time complexity: O(n) — keys are extracted and compared against a generated range.
 */
function isAssociativeArray(array $array): bool {
    if (empty($array)) return false;
    return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Trims the given string and returns null for null or empty results.
 *
 * This function normalizes empty or purely-whitespace strings to null:
 * - If the input is null, null is returned.
 * - Surrounding whitespace is removed from the input string.
 * - If the resulting trimmed string is an empty string, null is returned.
 * - Otherwise, the trimmed string is returned.
 *
 * @param string|null $string The input string to trim, or null.
 *
 * @return string|null The trimmed string, or null if the input was null or empty after trimming.
 */
function trimOrNull(?string $string): ?string
{
    if ($string === null) {
        return null;
    }

    $trimmed = trim((string) $string);
    return $trimmed === '' ? null : $trimmed;
}

/**
 * Trims whitespace from specified string fields within an associative array.
 *
 * This function iterates over $data and, for any key present in $trimmableFields,
 * applies trim() to the corresponding value. The input array is modified in-place
 * because it is passed by reference.
 *
 * Behavior:
 * - Only keys listed in $trimmableFields are processed.
 * - Values are expected to be strings; non-string values will be passed to trim()
 *   which may coerce them to strings or emit warnings depending on the PHP version.
 *
 * @param array &$data Associative array of data to sanitize. Keys matching $trimmableFields will have their values trimmed.
 *      Typical keys that may be trimmed (defaults):
 *      - name: string
 *      - description: string
 *      - startDateTime: string
 *      - completionDateTime: string
 *      - actualCompletionDateTime: string
 * @param string[] $trimmableFields Optional list of keys whose values should be trimmed. Defaults to:
 *      ['name', 'description', 'startDateTime', 'completionDateTime', 'actualCompletionDateTime']
 *
 * @return void
 */
function sanitizeData(
    array &$data,
    array $trimmableFields = [
        'name',
        'description',
        'startDateTime',
        'completionDateTime',
        'actualCompletionDateTime'
    ]
): void {
    foreach ($data as $key => $value) {
        if (in_array($key, $trimmableFields, true)) {
            $data[$key] = trim($value);
        }
    }
}

/**
 * Compares two date strings and determines their chronological order.
 *
 * This function converts both date strings to Unix timestamps and compares them:
 * - Returns 0 if both dates are equal
 * - Returns -1 if the first date is earlier than the second
 * - Returns 1 if the first date is later than the second
 *
 * @param string $date1 The first date string to compare (any format supported by strtotime)
 * @param string $date2 The second date string to compare (any format supported by strtotime)
 * 
 * @return int Comparison result:
 *      - 0 if dates are equal
 *      - -1 if $date1 is earlier than $date2
 *      - 1 if $date1 is later than $date2
 */
function compareDates(string $date1, string $date2): int
{
    $d1 = strtotime($date1);
    $d2 = strtotime($date2);

    if ($d1 === $d2) return 0;
    return ($d1 < $d2) ? -1 : 1;
}

/**
 * Converts a camelCase string to snake_case.
 *
 * This function inserts an underscore between a lowercase letter followed by an uppercase letter,
 * then converts the entire result to lowercase.
 *
 * Examples:
 * - "firstName" => "first_name"
 * - "startDateTime" => "start_date_time"
 * - "publicId" => "public_id"
 *
 * @param string $string Input string in camelCase
 *
 * @return string snake_case version of the provided string
 */
function camelToSnakeCase(string $string): string
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
}

/**
 * Converts a snake_case string to camelCase.
 *
 * This function performs the following steps:
 * - Replaces underscores ('_') with spaces
 * - Uppercases the first letter of each word (ucwords)
 * - Removes all spaces to join the words
 * - Lowercases the first character of the final string (lcfirst)
 *
 * Examples:
 * - "first_name" => "firstName"
 * - "start_date_time" => "startDateTime"
 * - "public_id" => "publicId"
 *
 * @param string $string Input string in snake_case
 *
 * @return string camelCase version of the provided string
 */
function snakeToCamelCase(string $string): string
{
    return lcfirst(str_replace('_', '', ucwords($string, '_')));
}

/**
 * Normalizes array keys to camelCase recursively.
 *
 * This function transforms all keys in an associative array from various formats
 * (snake_case, kebab-case, etc.) to camelCase. It recursively processes nested arrays.
 *
 * @param array $data The array with keys to normalize
 * @param bool $recursive Whether to recursively normalize nested arrays (default: true)
 *
 * @return array The array with all keys converted to camelCase
 */
function normalizeArrayKeysToCamelCase(array $data, bool $recursive = true): array
{
    // helper to convert a single string key from many cases to camelCase
    $toCamel = function (string $key): string {
        $key = trim($key);
        if ($key === '') return $key;

        // If contains separators (dash, underscore, space, dot), split and normalize
        if (preg_match('/[-_\s\.]/', $key)) {
            $parts = preg_split('/[-_\s\.]+/', $key);
            $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
            if (empty($parts)) return '';
            $parts = array_map('strtolower', $parts);
            $first = array_shift($parts);
            $rest = array_map(fn($p) => ucfirst($p), $parts);
            return lcfirst($first . implode('', $rest));
        }

        // Otherwise treat as camelCase or PascalCase: just lowercase first char
        return lcfirst($key);
    };

    $normalized = [];
    foreach ($data as $key => $value) {
        $newKey = is_string($key) ? $toCamel($key) : $key;

        // Recurse into arrays:
        if ($recursive && is_array($value)) {
            if (isAssociativeArray($value)) {
                $value = normalizeArrayKeysToCamelCase($value, $recursive);
            } else {
                // numeric-indexed list: normalize any associative children
                foreach ($value as $i => $item) {
                    if (is_array($item) && isAssociativeArray($item)) {
                        $value[$i] = normalizeArrayKeysToCamelCase($item, $recursive);
                    }
                }
            }
        }

        $normalized[$newKey] = $value;
    }

    return $normalized;
}

/**
 * Normalizes array keys to snake_case recursively.
 *
 * This function transforms all keys in an associative array from camelCase
 * to snake_case. It recursively processes nested arrays.
 *
 * @param array $data The array with keys to normalize
 * @param bool $recursive Whether to recursively normalize nested arrays (default: true)
 *
 * @return array The array with all keys converted to snake_case
 */
function normalizeArrayKeysToSnakeCase(array $data, bool $recursive = true): array
{
    $normalized = [];
    foreach ($data as $key => $value) {
        $newKey = is_string($key) ? camelToSnakeCase($key) : $key;

        // Recurse into arrays:
        if ($recursive && is_array($value)) {
            if (isAssociativeArray($value)) {
                $value = normalizeArrayKeysToSnakeCase($value, $recursive);
            } else {
                // numeric-indexed list: normalize any associative children
                foreach ($value as $i => $item) {
                    if (is_array($item) && isAssociativeArray($item)) {
                        $value[$i] = normalizeArrayKeysToSnakeCase($item, $recursive);
                    }
                }
            }
        }

        $normalized[$newKey] = $value;
    }

    return $normalized;
}