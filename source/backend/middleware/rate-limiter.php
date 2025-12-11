<?php

namespace App\Middleware;

use App\Exception\RateLimitException;
use App\Model\RateLimiterModel;
use Exception;
use InvalidArgumentException;

class RateLimiter
{


    /**
     * Handles rate limiting for requests coming from a given IP to a specific endpoint.
     *
     * This method validates provided options, checks the current rate-limit state for the
     * given IP/endpoint pair, and either creates or updates the rate-limit record. Behavior:
     * - Validates 'limit' and 'timeWindow' options as positive integers when provided.
     * - Uses sensible defaults when options are omitted:
     *     - limit: 100 requests
     *     - timeWindow: 1800 seconds (30 minutes)
     * - Searches for an existing rate-limit record for the IP and endpoint.
     * - If no record exists, creates a new rate-limit record (initial count handled by model).
     * - If a record exists and its time window has not expired:
     *     - Checks whether the next request would exceed the configured limit and throws
     *       RateLimitException if so.
     *     - Otherwise increments the stored request count and saves the record.
     * - If the existing record's time window has expired, resets the counter to 1 and saves
     *   the record with the new time window.
     * - Uses the current UNIX timestamp (time()) to determine expiration against the record's
     *   expiresAt value.
     *
     * @param string $ip Client IP address initiating the request.
     * @param string $endpoint Endpoint identifier being accessed.
     * @param array $options Associative array of optional configuration:
     *      - limit: int|null Maximum allowed requests within the time window (positive integer). Default: 100.
     *      - timeWindow: int|null Time window in seconds for rate limiting (positive integer). Default: 1800.
     *
     * @throws InvalidArgumentException If 'limit' or 'timeWindow' are provided but not positive integers.
     * @throws RateLimitException If the request would exceed the configured rate limit.
     *
     * @return void
     */
    public function handle(
        string $ip,
        string $endpoint,
        array $options = [
            'limit' => null,
            'timeWindow' => null
        ]
    ): void {
        if (isset($options['limit']) && (!is_int($options['limit']) || $options['limit'] <= 0)) {
            throw new InvalidArgumentException('Limit must be a positive integer.');
        }

        if (isset($options['timeWindow']) && (!is_int($options['timeWindow']) || $options['timeWindow'] <= 0)) {
            throw new InvalidArgumentException('Time window must be a positive integer.');
        }

        $limit = $options['limit'] ?? 100;
        $timeWindow = $options['timeWindow'] ?? 1800; // default 30 minutes

        $search = RateLimiterModel::search($ip, $endpoint);
        $currentTime = time();

        if (!$search) {
            // First request from this IP to this endpoint
            RateLimiterModel::create([
                'ip' => $ip,
                'endpoint' => $endpoint,
                'timeWindow' => $timeWindow
            ]);
        } else {
            $count = (int) ($search['count'] ?? 0);
            $expiresAt = (int) $search['expires_at'];
            $isExpired = $currentTime >= $expiresAt;

            if (!$isExpired) {
                if ($count + 1 > $limit) {
                    // Exceeded the rate limit
                    throw new RateLimitException('Rate limit exceeded. Please try again later.');
                }
                // Increment the counter
                RateLimiterModel::save([
                    'ip' => $ip,
                    'endpoint' => $endpoint,
                    'count' => $count + 1,
                    'timeWindow' => $timeWindow
                ]);
            } else {
                // Time window expired, reset the counter
                RateLimiterModel::save([
                    'ip' => $ip,
                    'endpoint' => $endpoint,
                    'count' => 1,
                    'timeWindow' => $timeWindow
                ]);
            }
        }
    }
}