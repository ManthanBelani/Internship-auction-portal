<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class UserController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * POST /api/users/register
     */
    public function register(array $data): void
    {
        try {
            // Validate required fields
            if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
                Response::badRequest('Email, password, and name are required');
                return;
            }

            // Optional role parameter (defaults to 'buyer')
            $role = $data['role'] ?? 'buyer';

            $result = $this->userService->registerUser(
                $data['email'],
                $data['password'],
                $data['name']
            );
            
            // Add role to response
            $result['role'] = $role;
            $result['status'] = 'active';

            Response::success($result, 201);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                Response::error('EMAIL_ALREADY_EXISTS', $e->getMessage(), 409);
            } else {
                Response::badRequest($e->getMessage());
            }
        }
    }

    /**
     * POST /api/users/login
     */
    public function login(array $data): void
    {
        try {
            // Validate required fields
            if (!isset($data['email']) || !isset($data['password'])) {
                Response::badRequest('Email and password are required');
                return;
            }

            $result = $this->userService->authenticateUser(
                $data['email'],
                $data['password']
            );

            Response::success($result);
        } catch (\Exception $e) {
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * GET /api/users/profile
     */
    public function getProfile(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $profile = $this->userService->getUserProfile((int)$user['userId']);
            Response::success($profile);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * PUT /api/users/profile
     */
    public function updateProfile(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $profile = $this->userService->updateUserProfile(
                (int)$user['userId'],
                (int)$user['userId'],
                $data
            );

            Response::success($profile);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * GET /api/users/:userId/public
     */
    public function getPublicProfile(int $userId): void
    {
        try {
            $profile = $this->userService->getPublicProfile($userId);
            Response::success($profile);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }
}
