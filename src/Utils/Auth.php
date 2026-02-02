<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateToken(int $userId, string $email): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
        $expiresIn = (int)($_ENV['JWT_EXPIRES_IN'] ?? 604800); // 7 days default

        $payload = [
            'userId' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + $expiresIn
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function verifyToken(string $token): ?array
    {
        try {
            $secret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
