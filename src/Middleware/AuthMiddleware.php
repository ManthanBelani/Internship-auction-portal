<?php

namespace App\Middleware;

use App\Utils\Auth;
use App\Utils\Response;

class AuthMiddleware
{
    public static function authenticate(): ?array
    {
        $token = Auth::getTokenFromHeader();

        if (!$token) {
            Response::unauthorized('Missing authentication token');
            return null;
        }

        $payload = Auth::verifyToken($token);

        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
            return null;
        }

        return $payload;
    }
}
