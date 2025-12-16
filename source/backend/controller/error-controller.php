<?php

namespace App\Controller;

use App\Interface\Controller;

class ErrorController implements Controller
{
    /**
     * Renders the HTTP error view.
     *
     * This method includes the HTTP error template located at VIEW_PATH . 'http-error.php'
     * and makes the provided $component array available to that view. It is intended to
     * present a user-friendly error page (or API error payload) based on the information
     * supplied in $component. The method performs no return value and its primary effect
     * is to include and output the error view.
     *
     * Typical keys accepted in the $component array:
     * - code: int|string HTTP status code (e.g. 404, 500)
     * - title: string Short title for the error page
     * - message: string Human-readable error message
     * - details: string|array Optional detailed information or debug data (avoid exposing sensitive data)
     * - headers: array Optional headers to send with the response
     * - meta: array Optional additional metadata for the view
     *
     * @param array $component Associative array of data to be consumed by the error view. Keys:
     *      - code: int|string HTTP status code
     *      - title: string Error title shown to the user
     *      - message: string Error message or description
     *      - details: string|array (optional) Additional debug or contextual details
     *      - headers: array (optional) Response headers to send
     *      - meta: array (optional) Any extra metadata required by the view
     *
     * @return void Outputs the included error view; does not return a value
     */
    public static function index(array $component = []): void
    {
        require_once VIEW_PATH . 'http-error.php';
    }

    /**
     * Handles HTTP 404 Not Found responses and renders the corresponding error page.
     *
     * This static method prepares and outputs a 404 error page by delegating rendering to
     * the controller's index method with appropriate metadata and then sets the HTTP
     * response status code:
     * - Calls self::index() with title '404 Not Found' and status '404'
     * - Sends HTTP status code 404 to the client via http_response_code(404)
     *
     * Use this method when a requested resource cannot be found. It produces output
     * side effects (renders content and sets the response code) and does not return a value.
     *
     * @return void
     * @see self::index()
     */
    public static function notFound(): void
    {
        self::index([
            'title' => '404 Not Found',
            'status' => '404'
        ]);
        http_response_code(404);
    }

    /**
     * Renders a 403 Forbidden error page and sets the HTTP response code.
     *
     * This static method prepares the error view and ensures the HTTP response
     * status reflects the forbidden condition:
     * - Calls self::index() with view data including 'title' and 'status'
     * - Sets the HTTP response code to 403 via http_response_code()
     *
     * @return void
     */
    public static function forbidden(): void
    {
        self::index([
            'title' => '403 Forbidden',
            'status' => '403'
        ]); 
        http_response_code(403);
    }
}