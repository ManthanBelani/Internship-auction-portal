<?php

namespace App\Services;

use App\Models\User;
use App\Utils\Auth;
use App\Config\Database;

class UserService
{
    private User $userModel;
    private ReviewService $reviewService;

    public function __construct()
    {
        $this->userModel = new User();
        $db = Database::getConnection();
        $this->reviewService = new ReviewService($db);
    }

    /**
     * Register a new user
     * 
     * @param string $email User email
     * @param string $password Plain text password (will be hashed)
     * @param string $name User name
     * @return array User data with JWT token
     * @throws \Exception If validation fails or email exists
     */
    public function registerUser(string $email, string $password, string $name): array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Validate password length (minimum 8 characters)
        if (strlen($password) < 8) {
            throw new \Exception('Password must be at least 8 characters long');
        }

        // Validate name
        if (empty(trim($name))) {
            throw new \Exception('Name is required');
        }

        // Check if email already exists
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) {
            throw new \Exception('Email already exists');
        }

        // Hash password
        $passwordHash = Auth::hashPassword($password);

        // Create user
        $user = $this->userModel->create($email, $passwordHash, $name);

        // Generate JWT token
        $token = Auth::generateToken((int)$user['id'], $user['email']);

        // Return user data without password hash
        return [
            'userId' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'] ?? 'buyer',
            'status' => $user['status'] ?? 'active',
            'registeredAt' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            'token' => $token
        ];
    }

    /**
     * Authenticate user with email and password
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @return array User data with JWT token
     * @throws \Exception If credentials are invalid
     */
    public function authenticateUser(string $email, string $password): array
    {
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        // Verify password
        if (!Auth::verifyPassword($password, $user['password'])) {
            throw new \Exception('Invalid credentials');
        }

        // Generate JWT token
        $token = Auth::generateToken((int)$user['id'], $user['email']);

        // Return user data without password hash
        return [
            'userId' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'] ?? 'buyer',
            'status' => $user['status'] ?? 'active',
            'registeredAt' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            'token' => $token
        ];
    }

    /**
     * Get user profile by user ID
     * 
     * @param int $userId User ID
     * @return array User profile data
     * @throws \Exception If user not found
     */
    public function getUserProfile(int $userId): array
    {
        $user = $this->userModel->findById($userId);

        // Get rating information
        $averageRating = $this->reviewService->calculateAverageRating($userId);
        $reviews = $this->reviewService->getReviewsForUser($userId);

        // Return profile without password hash
        return [
            'userId' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'registeredAt' => $user['created_at'],
            'averageRating' => $averageRating,
            'totalReviews' => count($reviews)
        ];
    }

    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param int $requestingUserId ID of user making the request
     * @param array $data Data to update (name, email)
     * @return array Updated user profile
     * @throws \Exception If user not found, unauthorized, or validation fails
     */
    public function updateUserProfile(int $userId, int $requestingUserId, array $data): array
    {
        // Prevent users from modifying other users' profiles
        if ($userId !== $requestingUserId) {
            throw new \Exception('You cannot modify another user\'s profile');
        }

        // Validate email if provided
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Validate name if provided
        if (isset($data['name']) && empty(trim($data['name']))) {
            throw new \Exception('Name cannot be empty');
        }

        // Update user
        $user = $this->userModel->update($userId, $data);

        // Return updated profile without password hash
        return [
            'userId' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'registeredAt' => $user['created_at']
        ];
    }

    /**
     * Get public user profile (excludes sensitive data)
     * 
     * @param int $userId User ID
     * @return array Public user profile
     * @throws \Exception If user not found
     */
    public function getPublicProfile(int $userId): array
    {
        $user = $this->userModel->getPublicProfile($userId);

        // Get rating information
        $averageRating = $this->reviewService->calculateAverageRating($userId);
        $reviews = $this->reviewService->getReviewsForUser($userId);

        return [
            'userId' => (int)$user['id'],
            'name' => $user['name'],
            'registeredAt' => $user['created_at'],
            'averageRating' => $averageRating,
            'totalReviews' => count($reviews)
        ];
    }
}

