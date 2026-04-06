<?php

namespace App\Services;

use App\Config\Database;
use App\Utils\AppLogger;

/**
 * Security Service for BidOrbit
 * 
 * Provides security utilities for input validation, sanitization,
 * rate limiting, and protection against common attacks.
 */
class SecurityService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Sanitize input string
     */
    public static function sanitize(string $input): string
    {
        // Remove whitespace from both ends
        $input = trim($input);
        
        // Strip HTML tags
        $input = strip_tags($input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }

    /**
     * Sanitize an array of inputs
     */
    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Validate email address
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number (basic validation)
     */
    public static function validatePhone(string $phone): bool
    {
        // Remove common separators
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);
        
        // Check if it's a valid phone number (10-15 digits)
        return preg_match('/^\+?[0-9]{10,15}$/', $phone) === 1;
    }

    /**
     * Validate price/amount
     */
    public static function validatePrice($amount, float $min = 0, float $max = PHP_FLOAT_MAX): bool
    {
        if (!is_numeric($amount)) {
            return false;
        }
        
        $amount = (float)$amount;
        return $amount >= $min && $amount <= $max;
    }

    /**
     * Validate date string
     */
    public static function validateDate(string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate auction end time (must be in the future)
     */
    public static function validateAuctionEndTime(string $endTime): bool
    {
        if (!self::validateDate($endTime)) {
            return false;
        }
        
        $endDateTime = new \DateTime($endTime);
        $now = new \DateTime();
        
        // Must be at least 1 hour in the future
        $minEndTime = (clone $now)->modify('+1 hour');
        
        return $endDateTime > $minEndTime;
    }

    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Generate a secure random token
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash a password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a password against a hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check for SQL injection patterns
     */
    public static function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|TRUNCATE)\b)/i',
            '/(--)|(\/\*)|(\*\/)/',
            '/(\bOR\b|\bAND\b)\s*["\']?\d+["\']?\s*=\s*["\']?\d+/i',
            '/["\']\s*;\s*/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                AppLogger::warning('Potential SQL injection detected', ['input' => substr($input, 0, 100)]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for XSS patterns
     */
    public static function detectXss(string $input): bool
    {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript\s*:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                AppLogger::warning('Potential XSS detected', ['input' => substr($input, 0, 100)]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(
        array $file,
        array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        int $maxSize = 10485760 // 10MB
    ): array {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1048576) . 'MB)';
        }
        
        // Check file type using mime_content_type for security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        // Check if file is actually an image
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = 'Invalid image file';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mimeType' => $mimeType ?? null,
        ];
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = self::generateSecureToken(32);
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token, int $expiry = 3600): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check if token has expired
        if (time() - $_SESSION['csrf_token_time'] > $expiry) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['timestamp'] = date('Y-m-d H:i:s');
        
        AppLogger::warning("Security Event: $event", $context);
    }

    /**
     * Check if IP is blacklisted
     */
    public function isIpBlacklisted(string $ip): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM ip_blacklist 
                 WHERE ip_address = ? 
                 AND (expires_at IS NULL OR expires_at > NOW())"
            );
            $stmt->execute([$ip]);
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add IP to blacklist
     */
    public function blacklistIp(string $ip, string $reason, ?int $expiresInHours = null): bool
    {
        try {
            $expiresAt = $expiresInHours 
                ? date('Y-m-d H:i:s', strtotime("+{$expiresInHours} hours"))
                : null;
            
            $stmt = $this->db->prepare(
                "INSERT INTO ip_blacklist (ip_address, reason, expires_at, created_at)
                 VALUES (?, ?, ?, NOW())"
            );
            
            return $stmt->execute([$ip, $reason, $expiresAt]);
        } catch (\Exception $e) {
            AppLogger::error('Failed to blacklist IP', ['ip' => $ip, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Validate auction item data
     */
    public static function validateItemData(array $data): array
    {
        $errors = [];
        
        // Title validation
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) < 3) {
            $errors['title'] = 'Title must be at least 3 characters';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters';
        }
        
        // Description validation
        if (empty($data['description'])) {
            $errors['description'] = 'Description is required';
        } elseif (strlen($data['description']) < 10) {
            $errors['description'] = 'Description must be at least 10 characters';
        }
        
        // Starting price validation
        if (!isset($data['startingPrice'])) {
            $errors['startingPrice'] = 'Starting price is required';
        } elseif (!self::validatePrice($data['startingPrice'], 0.01)) {
            $errors['startingPrice'] = 'Starting price must be a positive number';
        }
        
        // Reserve price validation (optional)
        if (isset($data['reservePrice']) && !empty($data['reservePrice'])) {
            if (!self::validatePrice($data['reservePrice'], 0.01)) {
                $errors['reservePrice'] = 'Reserve price must be a positive number';
            } elseif ((float)$data['reservePrice'] < (float)$data['startingPrice']) {
                $errors['reservePrice'] = 'Reserve price must be at least the starting price';
            }
        }
        
        // End time validation
        if (empty($data['endTime'])) {
            $errors['endTime'] = 'End time is required';
        } elseif (!self::validateAuctionEndTime($data['endTime'])) {
            $errors['endTime'] = 'End time must be at least 1 hour in the future';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate bid data
     */
    public static function validateBidData(array $data, array $item): array
    {
        $errors = [];
        
        if (!isset($data['amount'])) {
            $errors['amount'] = 'Bid amount is required';
        } elseif (!self::validatePrice($data['amount'], 0.01)) {
            $errors['amount'] = 'Bid amount must be a positive number';
        } elseif ((float)$data['amount'] <= (float)$item['current_price']) {
            $errors['amount'] = 'Bid amount must be higher than current price';
        }
        
        // Minimum bid increment (5% or $1, whichever is higher)
        $minIncrement = max((float)$item['current_price'] * 0.05, 1.0);
        $bidAmount = (float)$data['amount'];
        $requiredMin = (float)$item['current_price'] + $minIncrement;
        
        if ($bidAmount < $requiredMin) {
            $errors['amount'] = "Minimum bid increment is \${$minIncrement}";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
