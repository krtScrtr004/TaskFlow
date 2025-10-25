<?php

namespace Middleware;

use App\Core\Session;
use App\Exception\ForbiddenException;

class Csrf {
     // Generate token and store in session
    public static function generate(): string {
        if (!Session::has('csrfToken')) {
            Session::set('csrfToken', bin2hex(random_bytes(32)));
        }
        return Session::get('csrfToken');
    }
    // Validate token from POST request
    public static function validate(string $token): bool {
        if (!Session::has('csrfToken') || !$token) {
            return false;
        }

        // Timing-safe comparison
        return hash_equals(Session::get('csrfToken'), $token);
    }

    // Middleware-like protection
    public static function protect(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrfToken'] ?? '';

            if (!self::validate($token)) {
                throw new ForbiddenException('CSRF Protection: Invalid Token');
            }
        }
    }
}