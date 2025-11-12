<?php

namespace App\Core;

use App\Middleware\Csrf;

define('SESSION_LIFETIME', 3600);  // Cookie lifetime: 1 hour
define('SESSION_ACTIVITY_TIMEOUT', 1800);  // Inactivity timeout: 30 minutes
define('SESSION_PATH', '/TaskFlow/');  // Must match your web application path, not file system path
define('SESSION_DOMAIN', 'localhost');
define('SESSION_SECURE', false);  // Set to true only when using HTTPS in production
define('SESSION_HTTPONLY', true);

class Session
{
    private static ?Session $session = null;

    private function __construct()
    {
        // Must set these BEFORE session_start() to take effect
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);

            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => SESSION_PATH,
                'domain' => SESSION_DOMAIN,
                'secure' => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY
            ]);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check and handle session inactivity
        $this->checkInactivity();
    }

    /**
     * Checks if the session has been inactive for too long and destroys it if so.
     * On every request, updates the last activity timestamp to track user activity.
     * If user is active, the session is refreshed (renewed).
     * 
     * @return void
     */
    private function checkInactivity(): void
    {
        $now = time();
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $inactivityDuration = $now - $lastActivity;

        // If session has no last_activity timestamp or user has been inactive too long, destroy it
        if ($lastActivity === 0) {
            // First request of the session - initialize timestamp
            $_SESSION['last_activity'] = $now;
        } elseif ($inactivityDuration > SESSION_ACTIVITY_TIMEOUT) {
            // User inactive for more than SESSION_ACTIVITY_TIMEOUT seconds - expire the session
            $this->destroy();
        } else {
            // User is active - update the last activity timestamp
            $_SESSION['last_activity'] = $now;
            
            // Regenerate session ID every 5 minutes for security
            $lastRegenerate = $_SESSION['last_regenerate'] ?? 0;
            if ($now - $lastRegenerate > 300) {  // 300 seconds = 5 minutes
                $this->regenerate(false);
                $_SESSION['last_regenerate'] = $now;
            }
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
            // Store CSRF token before regeneration to prevent losing it
            $csrfToken = $_SESSION['csrf_token'] ?? null;
            
            session_regenerate_id($deleteOldSession);
            
            // Restore CSRF token after regeneration
            if ($csrfToken) {
                $_SESSION['csrf_token'] = $csrfToken;
            }
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
