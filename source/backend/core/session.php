<?php

namespace App\Core;

use App\Middleware\Csrf;

define('SESSION_LIFETIME', 3600);  // Cookie lifetime: 1 hour
define('SESSION_ACTIVITY_TIMEOUT', 1800);  // Inactivity timeout: 30 minutes
define('SESSION_PATH', '/');  // Must match your web application path, not file system path
define('SESSION_DOMAIN', 'TaskFlow.local');  // Set to your domain
define('SESSION_SECURE', false);  // Set to true only when using HTTPS in production
define('SESSION_HTTPONLY', true);

class Session
{
    private static ?Session $session = null;

    /**
     * Initializes and starts the application's session environment.
     *
     * This private constructor performs session configuration and ensures a session is active:
     * - When no session exists yet (PHP_SESSION_NONE), it sets strict and cookie-only modes:
     *     - ini_set('session.use_only_cookies', 1)
     *     - ini_set('session.use_strict_mode', 1)
     * - It then configures session cookie parameters via session_set_cookie_params() using
     *   the following constants (these must be set before session_start()):
     *     - 'lifetime' => SESSION_LIFETIME
     *     - 'path'     => SESSION_PATH
     *     - 'domain'   => SESSION_DOMAIN
     *     - 'secure'   => SESSION_SECURE
     *     - 'httponly' => SESSION_HTTPONLY
     * - If the session is not already active, it calls session_start() to begin the session.
     * - After ensuring the session is active, it invokes $this->checkInactivity() to handle
     *   session inactivity timeout/cleanup logic.
     *
     * Notes:
     * - Session configuration related to cookies must be applied prior to session_start() to take effect.
     * - session_start() may emit warnings or errors depending on PHP configuration and environment.
     *
     * @internal Private constructor used to configure and start the session for this class.
     * @return void
     * @see session_start()
     * @see session_set_cookie_params()
     * @see session_status()
     */
    private function __construct()
    {
        // Must set these BEFORE session_start() to take effect
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);

            session_set_cookie_params([
                'lifetime'      => SESSION_LIFETIME,
                'path'          => SESSION_PATH,
                'domain'        => SESSION_DOMAIN,
                'secure'        => SESSION_SECURE,
                'httponly'      => SESSION_HTTPONLY
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

    /**
     * Creates or returns the singleton Session instance.
     *
     * This method implements lazy initialization for the session singleton:
     * - Instantiates a new Session (new self()) when no instance exists
     * - Caches the created instance in the static self::$session property
     * - Returns the existing instance on subsequent calls to ensure a single shared instance
     *
     * Usage:
     * - Call Session::create() to obtain the shared Session object for the current execution context.
     *
     * Notes:
     * - This implements a simple singleton pattern; concurrency guarantees depend on the execution environment.
     *
     * @return self Singleton Session instance (newly created or previously cached)
     */
    public static function create(): self
    {
        if (!self::$session) {
            self::$session = new self();
        }
        return self::$session;
    }

    /**
     * Determines whether a PHP session has been started and is currently active.
     *
     * This method wraps PHP's session_status() and returns true only when the
     * session state equals PHP_SESSION_ACTIVE. It does not attempt to start or
     * resume a session; it only checks the current session status.
     *
     * @return bool True if a session is active (PHP_SESSION_ACTIVE), false otherwise.
     */
    public static function isSet(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Restores the application session and rehydrates the Me singleton from session data.
     *
     * Ensures a session is available and, if a serialized user is present in the session under
     * the 'userData' key and the Me instance has not yet been created, restores that instance.
     *
     * Behavior:
     * - If no session is set, a session is created via Session::create().
     * - If Session::has('userData') is true and Me::getInstance() === null, the stored data is
     *   retrieved with Session::get('userData') and used to instantiate Me via Me::instantiate().
     * - If a session already exists or Me is already instantiated, the method performs no action.
     *
     * Notes:
     * - The expected shape and type of 'userData' is determined by Me::instantiate() and should
     *   match whatever format was originally stored in the session.
     * - This method has side effects (creating a session and/or instantiating the Me singleton).
     *
     * @return void
     */
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

    /**
     * Stores a value in the session under the specified key.
     *
     * This utility writes directly to the $_SESSION superglobal.
     * - Overwrites any existing value at the given key.
     * - Accepts any PHP value (scalar, array, object); objects and arrays will be serialized by PHP when the session is saved.
     * - Does not start a session automatically — ensure session_start() has been called before invoking this method.
     *
     * @param string $key   Session key under which the value will be stored.
     * @param mixed  $value Value to store in the session (scalar, array, object, etc.).
     *
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a value from the session by its key.
     *
     * Returns the value stored in the $_SESSION superglobal for the given key,
     * or null if the key is not present. This function performs a plain lookup
     * and does not perform any type conversion or validation of the stored value.
     *
     * - Reads value from $_SESSION[$key]
     * - Returns null when the key does not exist
     * - Does not start the session; session_start() must have been called beforehand
     *
     * @param string $key The session key to retrieve
     *
     * @return mixed|null The value associated with the key in the session, or null if not set
     */
    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Determine whether a given key exists in the current session.
     *
     * This method checks the $_SESSION superglobal for the presence of the provided key.
     * It uses PHP's isset() semantics:
     * - Returns true only if the key exists in $_SESSION and its value is not null.
     * - Returns false if the key is absent or its value is null.
     *
     * Notes:
     * - This method does not start or resume a session (it does not call session_start()).
     *   It is safe to call even if a session has not been started; isset() will simply return false.
     * - This is a lightweight existence check and does not perform type or content validation
     *   of the stored value.
     *
     * @param string $key Session array key to check for existence.
     *
     * @return bool True if the session contains the key and its value is not null, false otherwise.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Regenerates the PHP session ID while preserving the CSRF token.
     *
     * This method:
     * - Verifies that the session wrapper is set (self::isSet()) and does nothing if not.
     * - Temporarily stores the CSRF token from $_SESSION['csrf_token'] to avoid losing it during regeneration.
     * - Calls session_regenerate_id($deleteOldSession) to replace the session identifier and optionally remove the old session data.
     * - Restores the stored CSRF token into the new session if a non-empty token was saved.
     *
     * Notes:
     * - The PHP session must be active (session_start() called) before invoking this method.
     * - Only a truthy CSRF token value will be restored (null, empty string, 0, etc. will not be reinserted).
     * - Regenerating the session ID can invalidate external references to the previous session identifier.
     *
     * @param bool $deleteOldSession Whether to delete the old session data after regeneration (default: true)
     *
     * @return void
     */
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

    /**
     * Removes an entry from the session storage.
     *
     * Unsets the value associated with the given key from the $_SESSION superglobal.
     * If the specified key does not exist, this method performs no action and raises no error.
     * Note: The session must be started (session_start()) before calling this method; this method
     * does not start, destroy, or regenerate the session itself. This operation only unsets a
     * top-level session key — it does not perform deep removal inside nested arrays unless a
     * matching top-level key is provided.
     *
     * @param string $key The session key to remove from $_SESSION.
     *
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Clears all current session variables and associated application user state.
     *
     * This method performs the following actions when a session is present:
     * - Verifies a session exists via self::isSet().
     * - Unsets all PHP session variables using session_unset().
     * - Invokes Me::destroy() to remove the application's representation of the authenticated user or session state.
     *
     * Important notes:
     * - This does not call session_destroy() or remove the session cookie; use session_destroy() if the session itself must be terminated.
     * - session_unset() only affects a session if one is active (session_start() must have been called).
     *
     * @return void No value is returned.
     * @see session_unset()
     * @see Me::destroy()
     */
    public static function clear(): void
    {
        if (self::isSet()) {
            session_unset();  // Clear all session variables
            Me::destroy();
        }
    }

    /**
     * Destroys the current session and resets the session singleton.
     *
     * This method performs the following actions when a session is set:
     * - Clears the $_SESSION superglobal to remove all session variables.
     * - Calls session_destroy() to terminate the active PHP session.
     * - Invokes Me::destroy() to clear any per-session "current user" or related state.
     * - Resets the internal session singleton (self::$session) to null.
     *
     * After this method returns, session data for the current request is removed and the
     * session singleton will need to be reinitialized before further session operations.
     *
     * @return void
     * @see Me::destroy()
     */
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
