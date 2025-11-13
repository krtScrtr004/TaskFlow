<?php

namespace App\Service;

use App\Utility\Email;

class AuthService
{
    public function __construct()
    {
    }

    /**
     * Sends a password reset link to the specified email address.
     *
     * This method generates a temporary password reset link containing the provided token,
     * and sends it to the user's email. The link is valid for 5 minutes.
     *
     * @param string $email The recipient's email address.
     * @param string $token The password reset token to be included in the link.
     *
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */
    public function sendLinkForPasswordReset(string $email, string $token): bool
    {
        if (!trimOrNull($email) || !trimOrNull($token)) {
            return false;
        }

        $link = REDIRECT_PATH . 'change-password?token=' . urlencode($token);

        return $this->sendTemporaryLink(
            $email,
            'Password Reset Link',
            "
                <p>We received a request to reset your password. Click the link below to reset it:</p>
                <p><a href='{$link}'>Reset Password</a></p>
                <p>This link will expire in 5 minutes.</p>
            "
        );
    }

    /**
     * Sends an email verification link to the specified email address.
     *
     * This method generates a verification link containing the provided token and sends it to the user's email.
     * The link allows the user to verify their email address and is valid for 30 days.
     *
     * @param string $email The recipient's email address.
     * @param string $token The unique verification token to be included in the link.
     *
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */
    public function sendLinkForEmailVerification(string $email, string $token): bool
    {
        if (!trimOrNull($email) || !trimOrNull($token)) {
            return false;
        }

        $link = REDIRECT_PATH . 'confirm-email?token=' . urlencode($token);

        return $this->sendTemporaryLink(
            $email,
            'Email Verification Link',
            "
                <p>Thank you for registering! Please verify your email address by clicking the link below:</p>
                <p><a href='{$link}'>Verify Email</a></p>
                <p>This link will expire in 30 Days.</p>
            "
        );
    }

    /**
     * Sends a temporary link to the specified email address.
     *
     * This method sends an email containing a temporary link, typically used for password resets or account verification.
     *
     * @param string $email Recipient's email address
     * @param string $subject Subject of the email
     * @param string $body Body content of the email, usually containing the temporary link
     *
     * @return bool Returns true if the email was sent successfully, false otherwise
     */
    private function sendTemporaryLink(string $email, string $subject, string $body): bool
    {
        return Email::send($email, $subject, $body);
    }
}