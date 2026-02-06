<?php

namespace App\Middleware;

class CorsMiddleware
{
    /**
     * Handle CORS headers
     */
    public static function handle(): bool
    {
        $allowedOrigins = self::getAllowedOrigins();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Check if origin is allowed
        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        return true;
    }

    /**
     * Get allowed origins from environment
     */
    private static function getAllowedOrigins(): array
    {
        $origins = $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:3000,http://localhost:8080';
        
        if ($origins === '*') {
            return ['*'];
        }

        return array_map('trim', explode(',', $origins));
    }

    /**
     * Middleware function for use with router
     */
    public static function middleware(): callable
    {
        return function() {
            return self::handle();
        };
    }
}
