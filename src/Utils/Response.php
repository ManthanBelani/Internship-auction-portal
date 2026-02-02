<?php

namespace App\Utils;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function success(array $data, int $statusCode = 200): void
    {
        self::json($data, $statusCode);
    }

    public static function error(string $code, string $message, int $statusCode = 400, ?array $details = null): void
    {
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        self::json($response, $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error('NOT_FOUND', $message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error('UNAUTHORIZED', $message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error('FORBIDDEN', $message, 403);
    }

    public static function badRequest(string $message, string $code = 'BAD_REQUEST'): void
    {
        self::error($code, $message, 400);
    }

    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error('INTERNAL_ERROR', $message, 500);
    }
}
