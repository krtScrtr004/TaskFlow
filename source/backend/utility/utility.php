<?php

// Helper to get a request header in a portable way
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

function kebabToCamelCase(string $string): string
{
    // Converts kebab-case to camelCase
    return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
}

function kebabToSentenceCase(string $string): string
{
    return str_replace('-', ' ', $string);
}

function camelToSentenceCase(string $string): string
{
    // Converts camelCase to sentence case
    return ucfirst(trimOrNull(preg_replace('/([a-z])([A-Z])/', '$1 $2', $string)));
}

function camelToKebabCase(string $string): string
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
}

function sentenceToSnakeCase(string $string): string 
{
    return strtolower(str_replace(' ', '_', $string));
}

function sentenceToKebabCase(string $string): string
{
    // Converts sentence case to kebab-case
    return strtolower(str_replace(' ', '-', trimOrNull($string)));
}

function sentenceToCamelCase(string $string): string
{
    // Converts sentence case to camelCase
    return lcfirst(str_replace(' ', '', ucwords($string)));
}

function formatBudgetToCents(float $amount): int {
    return (int) round($amount * 100);
}

function formatBudgetToPesos(int $amountInCents): float {
    return (float) $amountInCents / 100;
}

function isAssociativeArray(array $array): bool {
    if (empty($array)) return false;
    return array_keys($array) !== range(0, count($array) - 1);
}

function trimOrNull(?string $string): ?string
{
    if ($string === null) {
        return null;
    }

    $trimmed = trim((string) $string);
    return $trimmed === '' ? null : $trimmed;
}

function sanitizeData(
    array &$data,
    array $trimmableFields = [
        'name',
        'description',
        'startDateTime',
        'completionDateTime',
        'actionDateTime'
    ]
): void {
    foreach ($data as $key => $value) {
        if (in_array($key, $trimmableFields, true)) {
            $data[$key] = trim($value);
        }
    }
}

function generateRandomString(int $length = 16): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}