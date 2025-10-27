<?php

namespace App\Middleware;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\Session;
use App\Exception\ForbiddenException;

class Csrf {    
    public static function get(): ?string {
        return Session::get('csrfToken');
    }

    public static function set(string $token): void {
        Session::set('csrfToken', $token);
    }

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
        if (HttpAuth::isPOSTRequest()) {
            $token = getRequestHeader('X-CSRF-Token') ?? '';

            if (!self::validate($token)) {
                throw new ForbiddenException('CSRF Protection: Invalid Token');
            }
        }
    }
}