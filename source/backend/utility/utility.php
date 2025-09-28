<?php

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
    return ucfirst(trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', $string)));
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
    return strtolower(str_replace(' ', '-', trim($string)));
}
