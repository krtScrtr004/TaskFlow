<?php

namespace App\Utility;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    private static ?PHPMailer $mail = null;

    /**
     * Initializes the PHPMailer instance for sending emails via SMTP.
     *
     * This constructor sets up the PHPMailer object with the following configuration:
     * - Creates a new PHPMailer instance if one does not already exist.
     * - Configures the mailer to use SMTP for sending emails.
     * - Sets the SMTP server to Gmail's SMTP server.
     * - Enables SMTP authentication.
     * - Uses environment variables for SMTP username, password, and port.
     * - Sets the encryption method to implicit TLS.
     * - Configures the SMTP port from environment variable.
     *
     * @throws PHPMailer\PHPMailer\Exception If mailer initialization fails.
     */
    private function __construct()
    {
        if (!self::$mail) {
            //Create an instance; passing `true` enables exceptions
            self::$mail = new PHPMailer(true);

            // Server settings
            // $mail->SMTPDebug  = SMTP::DEBUG_SERVER;                    // Enable verbose debug output
            self::$mail->isSMTP();                                        // Send using SMTP
            self::$mail->Host = 'smtp.gmail.com';                         // Set the SMTP server to send through
            self::$mail->SMTPAuth = true;                                 // Enable SMTP authentication
            self::$mail->Username = $_ENV['MAIL_USERNAME'];               // SMTP username
            self::$mail->Password = $_ENV['MAIL_PASSWORD'];               // SMTP password; enable 2factor authentication and use app password
            self::$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        // Enable implicit TLS encryption
            self::$mail->Port = $_ENV['MAIL_PORT'];                       // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS
        }
    }

    /**
     * Sends a plain-text email.
     *
     * This method ensures the 'userFrom' display name is set (defaults to 'TaskFlow.com')
     * when not provided in the $data array and delegates the actual sending to self::send()
     * with HTML mode disabled.
     *
     * @param string $to Recipient email address
     * @param string $subject Subject of the email
     * @param string $body Plain-text body of the email
     * @param array $data Optional associative array of additional email options:
     *      - userFrom: string Display name for the sender (defaults to 'TaskFlow.com')
     *      - userTo: string Display name for the recipient (defaults to 'Valued User')
     *
     * @return bool True if the email was sent successfully, false otherwise
     */
    public static function sendPlain(
        string $to, 
        string $subject, 
        string $body, 
        array $data = []
    ): bool {
        return self::send($to, $subject, $body, $data, false);
    }
    
    /**
     * Sends an HTML formatted email.
     *
     * This method prepares and dispatches an email using HTML body format.
     * - Ensures a default sender display name ('userFrom') is present in $data
     * - Delegates the actual sending to self::send(..., true) which performs the low-level send
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject line
     * @param string $body HTML content of the email
     * @param array $data Optional associative array of additional mail options with following keys:
     *      - userFrom: string (optional) Display name for the sender; defaults to 'TaskFlow.com'
     *      - userTo: string (optional) Display name for the recipient; defaults to 'Valued User'
     *
     * @return bool True if the email was accepted/sent successfully, false on failure
     */
    public static function sendHtml(
        string $to, 
        string $subject, 
        string $body, 
        array $data = []
    ): bool {
        return self::send($to,  $subject, $body, $data, true);
    }

    /**
     * Sends an email to the specified recipient.
     *
     * This method performs the following actions:
     * - Checks if the current session is authorized; throws ForbiddenException if not.
     * - Initializes a new mailer instance to ensure a fresh state.
     * - Sets the sender's email address and name using environment variables.
     * - Adds the recipient's email address and username.
     * - Configures the email content as HTML, sets the subject and body.
     * - Attempts to send the email and returns true on success, false on failure.
     *
     * @param string $to Recipient's email address.
     * @param string $subject Subject of the email.
     * @param string $body HTML content of the email body.
     * @param array $data Associative array containing additional data:
     *      - userFrom: string (optional) Sender's username (used for display name).
     *      - userTo: string (optional) Recipient's username (used for display name).
     *      - replyTo: string (optional) Reply-To email address (for user submissions).
     * @param bool $isHtml Indicates whether the email body is in HTML format.
     *
     * @return bool True if the email was sent successfully, false otherwise.
     */
    private static function send(
        string $to, 
        string $subject, 
        string $body, 
        array $data = [], 
        bool $isHtml
    ): bool {
        $data['userFrom'] = $data['userFrom'] ?? 'TaskFlow.com';
        $data['userTo'] = $data['userTo'] ?? 'Valued User';

        try {
            // Create a new instance to ensure fresh state
            new self();

            // Recipients
            self::$mail->setFrom($_ENV['MAIL_USERNAME'], $data['userFrom']);
            self::$mail->addAddress($to, $data['userTo']); 

            // Set Reply-To if provided (for user-submitted emails)
            if (!empty($data['replyTo'])) {
                self::$mail->addReplyTo($data['replyTo'], $data['replyToName'] ?? '');
            }

            // Content
            if ($isHtml) {
                self::$mail->isHTML(true);  // Set email format to HTML
            }
            self::$mail->Subject = $subject;
            self::$mail->Body = $body;

            self::$mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}