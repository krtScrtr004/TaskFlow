<?php

class Logger
{
    private static $fileName = [
        'access'    => LOG_PATH . 'access-logs.txt',
        'error'     => LOG_PATH . 'error-logs.txt',
        'exception' => LOG_PATH . 'exception-logs.txt'
    ];

    private function __construct() {}

    /**
     * Logs an access event with a message and client IP address.
     *
     * This method records access events to a log file, including the current date and time,
     * the client's IP address, and a custom message. The log entry is appended to the access log file.
     * If the log file cannot be opened, an ErrorException is thrown.
     *
     * @param string $message The message describing the access event to be logged.
     *
     * @throws ErrorException If the access log file cannot be opened for writing.
     * 
     * @return void
     */
    public static function logAccess(String $message): void
    {
        $dateTime = new DateTime();
        $date = $dateTime->format("Y-m-d : h:i:s A");

        $ip = $_SERVER['REMOTE_ADDR'];

        $accessMessage = "[$date] - <$ip> $message" . PHP_EOL;

        $handle = fopen(self::$fileName['access'], 'a');
        if (!$handle) {
            throw new ErrorException('Cannot open ' . self::$fileName['access']);
        }

        fwrite($handle, $accessMessage);
        fclose($handle);
    }

    /**
     * Handles and logs PHP errors with detailed context information.
     *
     * This method formats the error details including the date, error number, error message,
     * file, and line number, and writes the formatted message to a designated error log file.
     * It ensures that all errors are consistently logged for debugging and auditing purposes.
     *
     * @param int $errno The level of the error raised
     * @param string $errstr The error message
     * @param string $errfile The filename that the error was raised in
     * @param int $errline The line number the error was raised at
     * 
     * @return bool Returns true after logging the error
     */
    public static function logError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $dateTime = new DateTime();
        $date = formatDateTime($dateTime, "Y-m-d : h:i:s A");

        $errorMessage = "[$date] [$errno] -> $errstr @ $errfile : $errline" . PHP_EOL;
        error_log($errorMessage, 3, self::$fileName['error']);

        return true;
    }

        /**
         * Logs an exception to the exception log file.
         *
         * This method formats the exception with a timestamp and appends it to the specified exception log file.
         * If the log file cannot be opened, an ErrorException is thrown.
         *
         * @param Throwable $exception The exception to be logged.
         *
         * @throws ErrorException If the exception log file cannot be opened for writing.
         *
         * @return void
         */
        public static function logException(Throwable $exception): void
        {
            $dateTime = new DateTime();
            $date = $dateTime->format("Y-m-d : h:i:s A");

            $exceptionMessage = "[$date] -> $exception\n" . PHP_EOL;
            $handle = fopen(self::$fileName['exception'], 'a');
            if (!$handle) {
            throw new ErrorException('Cannot open ' . self::$fileName['exception']);
        }

        fwrite($handle, $exceptionMessage);
        fclose($handle);
    }
}