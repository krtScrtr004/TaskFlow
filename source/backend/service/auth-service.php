<?php

namespace App\Service;

use App\Utility\Email;

class AuthService {
    public function __construct() {}

    public function sendTemporaryLink(string $email, string $token): bool {
        $link = REDIRECT_PATH . 'change-password?token=' . urlencode($token);

        return Email::send(
            $email,
            'Password Reset Link',
            "
                <p>We received a request to reset your password. Click the link below to reset it:</p>
                <p><a href='{$link}'>Reset Password</a></p>
                <p>This link will expire in 5 minutes.</p>
            "
        );
    }
}