<?php

namespace App\Core;

use App\Middleware\Csrf;

class Session
{
    private static ?Session $session = null;

    private function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function create(): self
    {
        if (!self::$session) {
            self::$session = new self();
        }
        return self::$session;
    }

    public static function isSet(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function restore(): void
    {
        if (!self::isSet()) {
            self::create();
        }

        // Restore Me instance from session data if it exists
        if (Session::has('userData') && Me::getInstance() === null) {
            $userData = Session::get('userData');
            Me::instantiate($userData);
        }
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

    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (self::isSet()) {
            session_regenerate_id($deleteOldSession);
        }
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function clear(): void
    {
        if (self::isSet()) {
            session_unset();  // Clear all session variables
            Me::destroy();
        }
    }

    public static function destroy(): void
    {
        if (self::isSet()) {
            $_SESSION = [];
            session_destroy();  // Completely destroy the session
            Me::destroy();
            self::$session = null;  // Reset the singleton instance
        }
    }
}
