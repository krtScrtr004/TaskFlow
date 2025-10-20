<?php

class Session
{
    private static ?self $instance = null;

    private function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function isSet(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function clear(): void
    {
        if (self::isSet()) {
            $_SESSION = [];
        }
    }

    public static function destroy(): void
    {
        if (self::isSet()) {
            $_SESSION = [];
            session_destroy();
        }
    }
}
