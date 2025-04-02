<?php
/**
 * Rate Limiting Service
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Services;

use App\Config\Database;
use PDO;

class RateLimitService
{
    private $db;
    private $maxRequests;
    private $windowMinutes;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->maxRequests = $_ENV['RATE_LIMIT_MAX_REQUESTS'];
        $this->windowMinutes = $_ENV['RATE_LIMIT_WINDOW_MINUTES'];
    }

    /**
     * Check if request is allowed
     * @param string $ipAddress Client IP address
     * @param string|null $token JWT token
     * @return array [allowed => bool, retryAfter => int|null]
     */
    public function checkRateLimit(string $ipAddress, ?string $token = null): array
    {
        $now = date('Y-m-d H:i:s');

        // Clean up old records
        $this->cleanupOldRecords($now);

        // Get current rate limit record
        $stmt = $this->db->prepare("
            SELECT * FROM rate_limits 
            WHERE ip_address = ? AND (token = ? OR (token IS NULL AND ? IS NULL))
        ");
        $stmt->execute([$ipAddress, $token, $token]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // Create new record
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (ip_address, token, request_count, first_request_at, last_request_at)
                VALUES (?, ?, 1, ?, ?)
            ");
            $stmt->execute([$ipAddress, $token, $now, $now]);
            return ['allowed' => true, 'retryAfter' => null];
        }

        // Check if within time window
        $firstRequest = strtotime($record['first_request_at']);
        $timeElapsed = time() - $firstRequest;

        if ($timeElapsed >= ($this->windowMinutes * 60)) {
            // Reset counter if window has passed
            $stmt = $this->db->prepare("
                UPDATE rate_limits 
                SET request_count = 1, first_request_at = ?, last_request_at = ?
                WHERE ip_address = ? AND (token = ? OR (token IS NULL AND ? IS NULL))
            ");
            $stmt->execute([$now, $now, $ipAddress, $token, $token]);
            return ['allowed' => true, 'retryAfter' => null];
        }

        // Check if request count exceeds limit
        if ($record['request_count'] >= $this->maxRequests) {
            $retryAfter = ($this->windowMinutes * 60) - $timeElapsed;
            return ['allowed' => false, 'retryAfter' => $retryAfter];
        }

        // Increment request count
        $stmt = $this->db->prepare("
            UPDATE rate_limits 
            SET request_count = request_count + 1, last_request_at = ?
            WHERE ip_address = ? AND (token = ? OR (token IS NULL AND ? IS NULL))
        ");
        $stmt->execute([$now, $ipAddress, $token, $token]);

        return ['allowed' => true, 'retryAfter' => null];
    }

    /**
     * Clean up old rate limit records
     * @param string $now Current timestamp
     */
    private function cleanupOldRecords(string $now): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM rate_limits 
            WHERE last_request_at < datetime(?, '-' || ? || ' minutes')
        ");
        $stmt->execute([$now, $this->windowMinutes]);
    }
} 