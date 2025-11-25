<?php

namespace App\Middleware;

class Response
{
    private function __construct()
    {
    }

    /**
     * Sends a standardized successful HTTP response.
     *
     * This method prepares a consistent response payload and delegates sending to sendResponse:
     * - Wraps the provided $data under the 'data' key
     * - Adds a 'status' key with the value 'success'
     * - Adds a human-readable 'message'
     * - Uses the provided HTTP status code (defaults to 200)
     *
     * The emitted payload structure:
     * [
     *     'status'  => 'success',
     *     'message' => string,
     *     'data'    => array
     * ]
     *
     * @param array  $data       Associative or indexed array containing the response payload to be included under the 'data' key.
     * @param string $message    Human-readable message describing the result (e.g., "OK", "Created", "Operation successful").
     * @param int    $statusCode HTTP status code to use for the response. Defaults to 200.
     *
     * @return void Sends the response via sendResponse and does not return a value.
     */
    public static function success(array $data, string $message, int $statusCode = 200): void
    {
        self::sendResponse($statusCode, [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Sends a standardized JSON error response and terminates the request flow.
     *
     * This method builds a response payload with a consistent structure and delegates
     * the actual output to self::sendResponse:
     * - Sets the response "status" to "error"
     * - Includes a human-readable "message"
     * - Includes an "errors" array with optional detailed error information
     * - Uses the provided HTTP status code (defaults to 400 Bad Request)
     *
     * @param string $message Short, human-readable error message describing the problem.
     * @param array $errors Optional array with detailed error information. Common formats:
     *      - A list of error strings: ['Invalid input', 'Missing field name']
     *      - An associative map of field => messages: ['email' => 'Invalid format']
     *      - An array of structured error objects: [['field' => 'age', 'message' => 'Must be >= 18']]
     * @param int $statusCode HTTP status code to send with the response (default: 400).
     *
     * @return void No value is returned; the response is sent immediately.
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        self::sendResponse($statusCode, [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ]);
    }

    /**
     * Sends a JSON HTTP response and terminates script execution.
     *
     * Performs the following actions in order:
     * - Sets the HTTP response status code via http_response_code()
     * - Sends the "Content-Type: application/json" header
     * - Attempts to clear the output buffer using ob_clean()
     * - Encodes the provided $body to JSON and writes it to output
     * - Calls exit() to stop further script execution
     *
     * Note: $body must be JSON-serializable. If json_encode() fails (e.g., due to unsupported types
     * or recursion), the output may be empty or contain a JSON error value depending on PHP settings.
     * Callers should ensure the data is encodable (convert objects to arrays, avoid resources, etc.).
     *
     * @param int   $statusCode HTTP status code to send (e.g. 200, 201, 400, 404, 500)
     * @param array $body       Associative array or other data structure that will be JSON-encoded as the response body (default: empty array)
     *
     * @return void This method terminates execution after sending the response (exit()).
     */
    private static function sendResponse(int $statusCode, array $body = [])
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode($body);
        exit();
    }
}