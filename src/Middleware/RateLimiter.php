<?php

namespace App\Middleware;

class RateLimiter
{
    private \PDO $db;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->db = \App\Config\Database::getConnection();
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Check if the request should be rate limited
     * 
     * @param string $key Unique identifier (IP, user ID, etc.)
     * @return bool True if request is allowed, false if rate limited
     */
    public function check(string $key): bool
    {
        $this->cleanupOldEntries();
        
        $attempts = $this->getAttempts($key);
        
        if ($attempts >= $this->maxAttempts) {
            \App\Utils\Response::json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $this->decayMinutes * 60
            ], 429);
            return false;
        }
        
        $this->incrementAttempts($key);
        return true;
    }

    /**
     * Get number of attempts for a key
     */
    private function getAttempts(string $key): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM rate_limits 
                    WHERE key_hash = :key 
                    AND created_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':key' => $this->hashKey($key),
                ':minutes' => $this->decayMinutes
            ]);
            
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            // If table doesn't exist, allow the request
            return 0;
        }
    }

    /**
     * Increment attempts for a key
     */
    private function incrementAttempts(string $key): void
    {
        try {
            $sql = "INSERT INTO rate_limits (key_hash, created_at) VALUES (:key, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':key' => $this->hashKey($key)]);
        } catch (\PDOException $e) {
            // Silently fail if table doesn't exist
        }
    }

    /**
     * Clean up old entries (run periodically)
     */
    private function cleanupOldEntries(): void
    {
        try {
            // Only cleanup 1% of the time to reduce overhead
            if (rand(1, 100) > 1) {
                return;
            }
            
            $sql = "DELETE FROM rate_limits 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':minutes' => $this->decayMinutes * 2]);
        } catch (\PDOException $e) {
            // Silently fail
        }
    }

    /**
     * Hash the key for storage
     */
    private function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Clear all attempts for a key (useful after successful login)
     */
    public function clear(string $key): void
    {
        try {
            $sql = "DELETE FROM rate_limits WHERE key_hash = :key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':key' => $this->hashKey($key)]);
        } catch (\PDOException $e) {
            // Silently fail
        }
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $key): int
    {
        $attempts = $this->getAttempts($key);
        return max(0, $this->maxAttempts - $attempts);
    }

    /**
     * Middleware function for use with router
     */
    public static function middleware(int $maxAttempts = 60, int $decayMinutes = 1): callable
    {
        return function() use ($maxAttempts, $decayMinutes) {
            $limiter = new self($maxAttempts, $decayMinutes);
            $key = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            return $limiter->check($key);
        };
    }
}
