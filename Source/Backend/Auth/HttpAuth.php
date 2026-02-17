<?php

namespace App\Auth;

class HttpAuth
{
    private function __construct()
    {
    }

    public static function isGETRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    public static function isPOSTRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isPUTRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'PUT';
    }

    public static function isPATCHRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'PATCH';
    }

    public static function isDELETERequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'DELETE';
    }
}