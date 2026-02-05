<?php

namespace App\Utility;

class TemporaryId 
{
    private static int $counter = -1;

    private function __construct() {}

    public static function generate(): int
    {
        return self::$counter--;
    }

    public static function isTemporary(int $id): bool
    {
        return $id < 0;
    }
}