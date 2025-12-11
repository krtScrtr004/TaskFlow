<?php

namespace App\Service;

use App\Utility\Email;

class AuthService
{
    public function __construct()
    {
    }

    /**
     * Sends a temporary password reset link to the specified email address.
     *
     * This method:
     * - Validates that both $email and $token are provided (non-empty after trimming).
     * - Constructs a password reset URL using the REDIRECT_PATH constant and the URL-encoded token
     *   as the 'token' query parameter.
     * - Calls sendTemporaryLink to deliver an HTML email with subject "Password Reset Link"
     *   containing the reset URL and a note that the link expires in 5 minutes.
     *
     * @param string $email Recipient email address (must be non-empty after trimming)
     * @param string $token One-time password reset token (must be non-empty after trimming)
     *
     * @return bool True if the reset email was successfully queued/sent; false if validation fails
     *              or sendTemporaryLink returns false.
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
     * Sends an email verification link to the given address.
     *
     * This method validates inputs and constructs a one-time email verification URL:
     * - Ensures both $email and $token are non-empty after trimming; returns false if invalid.
     * - Builds the verification URL by concatenating the REDIRECT_PATH constant with
     *   the confirm-email route and the URL-encoded token as a query parameter.
     * - Delegates actual delivery to sendTemporaryLink with an HTML message containing
     *   a clickable verification anchor and an expiry notice (30 days).
     *
     * @param string $email Recipient email address to which the verification link will be sent.
     * @param string $token Verification token to include in the URL (will be URL-encoded).
     *
     * @return bool True if the verification message was successfully handed off to the mailer,
     *              false if input validation fails or sending fails.
     *
     * @see sendTemporaryLink()
     * @see REDIRECT_PATH
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
     * Sends a temporary link to a user via email.
     *
     * This method delegates the actual sending to Email::send and returns that result.
     * The $body is expected to contain the temporary link (and any relevant instructions or expiry information).
     *
     * @param string $email Recipient email address
     * @param string $subject Subject line for the temporary link email
     * @param string $body Body of the email; should include the temporary link and any usage instructions
     *
     * @return bool True if the email was accepted/sent by the mailer, false on failure
     */
    private function sendTemporaryLink(string $email, string $subject, string $body): bool
    {
        return Email::sendHtml($email, $subject, $body);
        // return Email::sendPlain($_ENV['MAIL_USERNAME'], "Concern from $name", $message, [
        //     'userFrom' => 'TaskFlow Support',
        //     'userTo' => 'TaskFlow Support',
        //     'replyTo' => $email,
        //     'replyToName' => $name
        // ]);
    }
}