<?php

namespace App\Abstract;

use App\Middleware\RateLimiter;

abstract class Endpoint
{
    protected static RateLimiter $rateLimiter;

    abstract public static function getById(array $args = []);

    abstract public static function getByKey(array $args = []);

    abstract public static function create(array $args = []);

    abstract public static function edit(array $args = []);

    abstract public static function delete(array $args = []);

    /**
     * Builds a unique endpoint identifier for the current HTTP request.
     *
     * This method constructs a string by concatenating the request URI and the HTTP
     * method with a colon (':') delimiter. The resulting value is intended for use
     * in routing keys, logging, caching, or other places where a request-specific
     * identifier is required.
     *
     * Notes:
     * - Reads values from $_SERVER['REQUEST_URI'] and $_SERVER['REQUEST_METHOD'].
     * - Does not modify, normalize or decode the URI or method; the raw values
     *   from $_SERVER are used as-is.
     * - If the expected $_SERVER keys are not present, the behavior depends on the
     *   global state (may produce notices or empty segments); callers should ensure
     *   the environment is populated (e.g., when running under HTTP server or a
     *   properly configured test harness).
     *
     * Examples:
     * - Request URI "/api/items?limit=10" with method "GET" => "/api/items?limit=10:GET"
     * - Request URI "/" with method "POST" => "/:POST"
     *
     * @return string Endpoint name in the format "<request_uri>:<http_method>"
     */
    public static function getEndpointName(): string
    {
        return $_SERVER['REQUEST_URI'] . ':' . $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Retrieve the client's IP address from server variables.
     *
     * This method checks common server-provided headers in the following order:
     * 1. HTTP_CLIENT_IP
     * 2. HTTP_X_FORWARDED_FOR (may contain a comma-separated list of IPs)
     * 3. REMOTE_ADDR
     *
     * Behaviour details:
     * - If HTTP_X_FORWARDED_FOR contains multiple addresses, the header value is returned as-is;
     *   callers should parse and validate the first/appropriate entry if they need the originating IP.
     * - These headers can be spoofed by clients. For security-sensitive use cases, ensure headers are
     *   set or validated by a trusted proxy/load balancer before trusting the returned value.
     *
     * @return string Client IP address, or the literal string 'UNKNOWN' if no address is available
     */
    public static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }

    /**
     * Applies rate limiting for the current request and endpoint.
     *
     * This protected static helper ensures a RateLimiter instance exists and delegates
     * enforcement to it. Behavior:
     * - Lazily instantiates and stores a RateLimiter in a static property.
     * - Identifies the client by IP using self::getIpAddress().
     * - Scopes limits by endpoint using self::getEndpointName().
     * - Enforces the default policy of 60 requests per 60 seconds (60 requests per minute).
     *
     * The actual enforcement is performed by RateLimiter::handle(...) and may result in
     * whatever behavior that implementation defines when limits are exceeded (e.g. throwing
     * an exception, sending an HTTP response, etc.).
     * 
     * @param int $limit Maximum number of requests allowed within the time window. Default is 60.
     * @param int $timeWindow Time window in seconds for the rate limit. Default is 60.
     *
     * @throws \Throwable Propagates any exception/error produced by the underlying RateLimiter.
     * @return void
     */
    protected static function rateLimit(int $limit = 60, int $timeWindow = 60)
    {
        if (!isset(self::$rateLimiter)) {
            self::$rateLimiter = new RateLimiter();
        }

        self::$rateLimiter->handle(
            self::getIpAddress(),
            self::getEndpointName(),
            ['limit' => $limit, 'timeWindow' => $timeWindow]
        );
    }

    /**
     * Enforces rate limiting for the current endpoint and client.
     *
     * This protected static method ensures a RateLimiter instance exists and delegates
     * rate-limit enforcement to it for the current request. It is intended to be called
     * during endpoint handling to prevent excessive requests from the same client.
     *
     * Behavior:
     * - Lazily instantiates and caches a RateLimiter in self::$rateLimiter when needed.
     * - Identifies the client using self::getIpAddress().
     * - Scopes the limit to the current endpoint using self::getEndpointName().
     * - Applies a default policy of 3 requests per 60 seconds (['limit' => 3, 'timeWindow' => 60]).
     * - Delegates enforcement to RateLimiter::handle(), which may perform actions such as
     *   incrementing counters, sending response headers, throwing exceptions, or aborting the request
     *   depending on the RateLimiter implementation.
     *
     * @return void
     *
     * @throws \Throwable If rate limiter handling fails or if IP/endpoint resolution encounters an error.
     */
    protected static function formRateLimit()
    {
        if (!isset(self::$rateLimiter)) {
            self::$rateLimiter = new RateLimiter();
        }

        self::$rateLimiter->handle(
            self::getIpAddress(),
            self::getEndpointName(),
            ['limit' => 3, 'timeWindow' => 60] // 3 requests per minute
        );
    }
}