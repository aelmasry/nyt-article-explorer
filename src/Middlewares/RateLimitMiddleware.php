<?php
/**
 * Rate Limiting Middleware
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Middlewares;

use App\Services\RateLimitService;
use App\Services\JwtService;

class RateLimitMiddleware
{
    private $rateLimitService;
    private $jwtService;

    public function __construct()
    {
        $this->rateLimitService = new RateLimitService();
        $this->jwtService = new JwtService();
    }

    /**
     * Handle rate limiting
     */
    public function handle(): void
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $token = $this->jwtService->getTokenFromHeader();

        $result = $this->rateLimitService->checkRateLimit($ipAddress, $token);

        if (!$result['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . $result['retryAfter']);
            echo json_encode([
                'error' => 'Too many requests',
                'retry_after' => $result['retryAfter']
            ]);
            exit;
        }
    }
}