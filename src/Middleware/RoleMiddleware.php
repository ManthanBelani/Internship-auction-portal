<?php

namespace App\Middleware;

use App\Utils\Auth;
use App\Utils\Response;
use App\Models\User;

class RoleMiddleware
{
    /**
     * Check if user has required role
     * 
     * @param array $allowedRoles Array of allowed roles
     * @return array|null User data if authorized, null otherwise
     */
    public static function checkRole(array $allowedRoles): ?array
    {
        // Get and verify token
        $token = Auth::getTokenFromHeader();
        
        if (!$token) {
            Response::json(['error' => 'Authentication required'], 401);
            return null;
        }
        
        $payload = Auth::verifyToken($token);
        
        if (!$payload) {
            Response::json(['error' => 'Invalid or expired token'], 401);
            return null;
        }
        
        // Get user details including role
        try {
            $userModel = new User();
            $user = $userModel->findById($payload['userId']);
            
            // Check if user is suspended or banned
            if (isset($user['status']) && $user['status'] !== 'active') {
                if ($user['status'] === 'suspended') {
                    $message = 'Your account is suspended';
                    if ($user['suspended_until']) {
                        $message .= ' until ' . $user['suspended_until'];
                    }
                    Response::json(['error' => $message], 403);
                } else {
                    Response::json(['error' => 'Your account has been banned'], 403);
                }
                return null;
            }
            
            // Check role
            $userRole = $user['role'] ?? 'buyer';
            
            if (!in_array($userRole, $allowedRoles)) {
                Response::json([
                    'error' => 'Insufficient permissions',
                    'required_roles' => $allowedRoles,
                    'your_role' => $userRole
                ], 403);
                return null;
            }
            
            // Add role to payload
            $payload['role'] = $userRole;
            $payload['status'] = $user['status'] ?? 'active';
            
            return $payload;
            
        } catch (\Exception $e) {
            Response::json(['error' => 'User not found'], 404);
            return null;
        }
    }
    
    /**
     * Check if user is admin
     */
    public static function requireAdmin(): ?array
    {
        return self::checkRole(['admin']);
    }
    
    /**
     * Check if user is admin or moderator
     */
    public static function requireAdminOrModerator(): ?array
    {
        return self::checkRole(['admin', 'moderator']);
    }
    
    /**
     * Check if user is seller (or admin)
     */
    public static function requireSeller(): ?array
    {
        return self::checkRole(['admin', 'seller']);
    }
    
    /**
     * Check if user can buy (buyer, seller, or admin)
     */
    public static function requireBuyer(): ?array
    {
        return self::checkRole(['admin', 'seller', 'buyer']);
    }
    
    /**
     * Check if user owns a resource or is admin
     * 
     * @param int $resourceOwnerId Owner ID of the resource
     * @param int $userId Current user ID
     * @param string $userRole Current user role
     * @return bool
     */
    public static function canModifyResource(int $resourceOwnerId, int $userId, string $userRole): bool
    {
        // Admins can modify anything
        if ($userRole === 'admin') {
            return true;
        }
        
        // Users can only modify their own resources
        return $resourceOwnerId === $userId;
    }
    
    /**
     * Check if user can moderate content
     * 
     * @param string $userRole Current user role
     * @return bool
     */
    public static function canModerate(string $userRole): bool
    {
        return in_array($userRole, ['admin', 'moderator']);
    }
}
