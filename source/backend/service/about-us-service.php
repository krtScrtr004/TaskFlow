<?php

namespace App\Service;

use App\Utility\Email;

class AboutUsService 
{
    public function __construct()
    {
    }

    /**
     * Sends a concern email using the application's configured mail account.
     *
     * This method composes and sends a plain-text email with the subject
     * "Concern from {name}" using the MAIL_USERNAME environment value as the
     * sender. It attaches metadata indicating who the concern is from and that
     * the intended recipient is "TaskFlow Support".
     *
     * @param string $name    Name of the person submitting the concern; included in the subject and metadata.
     * @param string $email   Email address that will receive the message.
     * @param string $message Plain-text body of the concern message.
     *
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public function sendConcernEmail(string $name, string $email, string $message): bool
    {
        // Send FROM your authenticated Gmail account, set Reply-To to user's email
        return Email::sendPlain($_ENV['MAIL_USERNAME'], "Concern from $name", $message, [
            'userFrom' => 'TaskFlow Support',
            'userTo' => 'TaskFlow Support',
            'replyTo' => $email,
            'replyToName' => $name
        ]);
    }
}