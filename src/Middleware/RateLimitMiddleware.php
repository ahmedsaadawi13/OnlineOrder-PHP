<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storageDir;

    public function __construct()
    {
        $this->maxRequests = (int) env('RATE_LIMIT_MAX_REQUESTS', 60);
        $this->windowSeconds = (int) env('RATE_LIMIT_WINDOW', 60);
        $this->storageDir = __DIR__ . '/../../storage/cache/ratelimit/';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Handle rate limiting
     */
    public function handle(Request $request): ?Response
    {
        $key = $this->getRateLimitKey($request);
        $attempts = $this->getAttempts($key);
        $resetTime = $this->getResetTime($key);

        // Clean up old attempts
        $this->cleanupOldAttempts();

        if ($attempts >= $this->maxRequests) {
            $retryAfter = $resetTime - time();

            $response = new Response();
            $response->header('X-RateLimit-Limit', (string) $this->maxRequests);
            $response->header('X-RateLimit-Remaining', '0');
            $response->header('X-RateLimit-Reset', (string) $resetTime);
            $response->header('Retry-After', (string) max(0, $retryAfter));

            return $response->error(
                'Too many requests. Please try again later.',
                429,
                ['retry_after' => max(0, $retryAfter)]
            );
        }

        // Increment attempts
        $this->incrementAttempts($key);

        // Add rate limit headers to response
        // Note: This needs to be added to the actual response in Application class
        $_SERVER['X_RATELIMIT_LIMIT'] = $this->maxRequests;
        $_SERVER['X_RATELIMIT_REMAINING'] = $this->maxRequests - $attempts - 1;
        $_SERVER['X_RATELIMIT_RESET'] = $resetTime;

        return null;
    }

    /**
     * Get rate limit key for user/IP
     */
    private function getRateLimitKey(Request $request): string
    {
        // Use user ID if authenticated, otherwise use IP
        $identifier = $request->user['id'] ?? $request->ip();
        return 'ratelimit_' . md5($identifier);
    }

    /**
     * Get number of attempts
     */
    private function getAttempts(string $key): int
    {
        $file = $this->storageDir . $key;

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        $resetTime = $data['reset_time'] ?? 0;

        // Reset if window has expired
        if (time() >= $resetTime) {
            unlink($file);
            return 0;
        }

        return $data['attempts'] ?? 0;
    }

    /**
     * Get reset time
     */
    private function getResetTime(string $key): int
    {
        $file = $this->storageDir . $key;

        if (!file_exists($file)) {
            return time() + $this->windowSeconds;
        }

        $data = json_decode(file_get_contents($file), true);
        return $data['reset_time'] ?? time() + $this->windowSeconds;
    }

    /**
     * Increment attempts
     */
    private function incrementAttempts(string $key): void
    {
        $file = $this->storageDir . $key;
        $attempts = $this->getAttempts($key);
        $resetTime = $this->getResetTime($key);

        $data = [
            'attempts' => $attempts + 1,
            'reset_time' => $resetTime
        ];

        file_put_contents($file, json_encode($data));
    }

    /**
     * Clean up old rate limit files
     */
    private function cleanupOldAttempts(): void
    {
        // Run cleanup randomly (1% chance)
        if (rand(1, 100) !== 1) {
            return;
        }

        $files = glob($this->storageDir . 'ratelimit_*');
        $now = time();

        foreach ($files as $file) {
            if (filemtime($file) < $now - $this->windowSeconds - 3600) {
                @unlink($file);
            }
        }
    }
}
