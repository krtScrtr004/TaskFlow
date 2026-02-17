<?php

namespace App\Middleware;

use App\Auth\HttpAuth;
use App\Core\Session;
use App\Exception\ForbiddenException;

class Csrf {    
    /**
     * Retrieves the CSRF token stored in the current session.
     *
     * This static method fetches the CSRF token value saved under the 'csrfToken' session key:
     * - Returns the token as a string when present.
     * - Returns null if no token is stored in the session.
     * - Does not generate or refresh a token; use the appropriate token-creation method when needed.
     * - Intended for use when validating incoming requests/forms against the stored token.
     * - Relies on Session::get to access session data.
     *
     * @return string|null CSRF token string from session, or null if not set.
     */
    public static function get(): ?string {
        return Session::get('csrfToken');
    }

    /**
     * Stores a CSRF token in the session for later validation.
     *
     * This method delegates to the Session helper to persist the provided token under the
     * 'csrfToken' key so it can be used for subsequent CSRF protection checks.
     * - Stores token using Session::set('csrfToken', $token)
     * - Overwrites any existing CSRF token stored under the same key
     * - Token persists for the lifetime of the session
     *
     * @param string $token CSRF token to store in the session
     *
     * @return void
     */
    public static function set(string $token): void {
        Session::set('csrfToken', $token);
    }

    /**
     * Generates and returns a CSRF token for the current session.
     *
     * This method ensures a cryptographically secure token is available for the session:
     * - If no token exists under the session key 'csrfToken', a new token is generated with random_bytes(32)
     *   and stored as a hexadecimal string via Session::set('csrfToken', ...).
     * - If a token already exists, it is returned unchanged.
     *
     * Notes:
     * - The generated token is 32 bytes of cryptographic randomness encoded as a 64-character hexadecimal string.
     * - The method depends on the Session abstraction (Session::has, Session::set, Session::get) to persist the token.
     *
     * @return string CSRF token (64-character hex string) stored in the session under 'csrfToken'
     * @throws \Exception If random_bytes fails to produce sufficient entropy
     */
    public static function generate(): string {
        if (!Session::has('csrfToken')) {
            Session::set('csrfToken', bin2hex(random_bytes(32)));
        }
        return Session::get('csrfToken');
    }

    /**
     * Validate a CSRF token against the token stored in the session.
     *
     * This method verifies that a CSRF token exists in the session under the key
     * 'csrfToken' and that the provided token is non-empty. It performs a
     * timing-attack safe comparison using hash_equals to avoid leaking information
     * through timing differences.
     *
     * Notes:
     * - Requires an active session and a session value at key 'csrfToken'.
     * - Both the stored token and the provided token are expected to be strings.
     * - Returns false if the session token is missing or the provided token is empty.
     * - Does not modify session state.
     *
     * @param string $token CSRF token supplied by the client (e.g. form field or header). Must be a non-empty string.
     * @return bool True if the provided token matches the session token; false otherwise.
     */
    public static function validate(string $token): bool {
        if (!Session::has('csrfToken') || !$token) {
            return false;
        }

        // Timing-safe comparison
        return hash_equals(Session::get('csrfToken'), $token);
    }

    /**
     * Protects against Cross-Site Request Forgery (CSRF) for state-changing HTTP requests.
     *
     * When the incoming request uses a mutating HTTP method (POST, PATCH, PUT, DELETE),
     * this method attempts to retrieve the CSRF token from the "X-CSRF-Token" request header
     * and validates it. If the token is missing or invalid, a ForbiddenException is thrown.
     *
     * Behavior:
     * - Detects mutating requests using HttpAuth::isPOSTRequest(), HttpAuth::isPATCHRequest(),
     *   HttpAuth::isPUTRequest(), and HttpAuth::isDELETERequest().
     * - Reads the token from getRequestHeader('X-CSRF-Token') and treats a missing header as an empty string.
     * - Validates the token via self::validate($token).
     * - Does nothing for non-mutating (safe) HTTP methods.
     *
     * @return void
     *
     * @throws ForbiddenException If the request is mutating and the CSRF token is missing or invalid.
     *
     * @see HttpAuth::isPOSTRequest()
     * @see HttpAuth::isPATCHRequest()
     * @see HttpAuth::isPUTRequest()
     * @see HttpAuth::isDELETERequest()
     * @see self::validate()
     */
    public static function protect(): void {
        if (HttpAuth::isPOSTRequest() || HttpAuth::isPATCHRequest() || HttpAuth::isPUTRequest() || HttpAuth::isDELETERequest()) {
            $token = getRequestHeader('X-CSRF-Token') ?? '';

            if (!self::validate($token)) {
                throw new ForbiddenException('CSRF Protection: Invalid Token');
            }
        }
    }
}