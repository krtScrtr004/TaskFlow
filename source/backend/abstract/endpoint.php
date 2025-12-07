<?php

namespace App\Abstract;

abstract class Endpoint 
{
    abstract public static function getById(array $args = []);

    abstract public static function getByKey(array $args = []);

    abstract public static function create(array $args = []);

    abstract public static function edit(array $args = []);

    abstract public static function delete(array $args = []);

    public static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }
}