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

    public function isSet(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        if ($this->isSet()) {
            $_SESSION = [];
        }
    }

    public function destroy(): void
    {
        if ($this->isSet()) {
            $_SESSION = [];
            session_destroy();
        }
    }
}
