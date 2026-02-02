<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Services\ItemService;
use App\Services\CommissionService;
use App\Models\User;
use App\Middleware\RoleMiddleware;
use App\Utils\Response;
use App\Config\Database;

class AdminController
{
    private UserService $userService;
    private User $userModel;
    private CommissionService $commissionService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->userModel = new User();
        $db = Database::getConnection();
        $this->commissionService = new CommissionService($db);
    }

    /**
     * Get all users (Admin only)
     * GET /api/admin/users
     */
    public function getAllUsers(array $queryParams): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $filters = [];
            
            if (isset($queryParams['role'])) {
                $filters['role'] = $queryParams['role'];
            }
            
            if (isset($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }
            
            if (isset($queryParams['search'])) {
                $filters['search'] = $queryParams['search'];
            }

            $users = $this->userModel->getAllUsers($filters);

            Response::json([
                'users' => $users,
                'total' => count($users)
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update user role (Admin only)
     * PUT /api/admin/users/{userId}/role
     */
    public function updateUserRole(int $userId, array $data): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            if (!isset($data['role'])) {
                Response::json(['error' => 'Role is required'], 400);
                return;
            }

            $user = $this->userModel->updateRole($userId, $data['role']);

            Response::json([
                'message' => 'User role updated successfully',
                'user' => [
                    'userId' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'status' => $user['status']
                ]
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Suspend user (Admin or Moderator)
     * POST /api/admin/users/{userId}/suspend
     */
    public function suspendUser(int $userId, array $data): void
    {
        $user = RoleMiddleware::requireAdminOrModerator();
        if (!$user) return;

        try {
            $until = $data['until'] ?? null;
            
            $suspendedUser = $this->userModel->suspendUser($userId, $until);

            Response::json([
                'message' => 'User suspended successfully',
                'user' => [
                    'userId' => (int)$suspendedUser['id'],
                    'name' => $suspendedUser['name'],
                    'status' => $suspendedUser['status'],
                    'suspended_until' => $suspendedUser['suspended_until']
                ]
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Ban user (Admin only)
     * POST /api/admin/users/{userId}/ban
     */
    public function banUser(int $userId): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $bannedUser = $this->userModel->banUser($userId);

            Response::json([
                'message' => 'User banned successfully',
                'user' => [
                    'userId' => (int)$bannedUser['id'],
                    'name' => $bannedUser['name'],
                    'status' => $bannedUser['status']
                ]
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reactivate user (Admin or Moderator)
     * POST /api/admin/users/{userId}/reactivate
     */
    public function reactivateUser(int $userId): void
    {
        $user = RoleMiddleware::requireAdminOrModerator();
        if (!$user) return;

        try {
            $reactivatedUser = $this->userModel->reactivateUser($userId);

            Response::json([
                'message' => 'User reactivated successfully',
                'user' => [
                    'userId' => (int)$reactivatedUser['id'],
                    'name' => $reactivatedUser['name'],
                    'status' => $reactivatedUser['status']
                ]
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get platform statistics (Admin only)
     * GET /api/admin/stats
     */
    public function getStatistics(): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $db = Database::getConnection();
            
            // Total users by role
            $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $usersByRole = $stmt->fetchAll();
            
            // Total items by status
            $stmt = $db->query("SELECT status, COUNT(*) as count FROM items GROUP BY status");
            $itemsByStatus = $stmt->fetchAll();
            
            // Total transactions
            $stmt = $db->query("SELECT COUNT(*) as count FROM transactions");
            $totalTransactions = $stmt->fetch()['count'];
            
            // Platform earnings
            $totalEarnings = $this->commissionService->getTotalPlatformEarnings();

            Response::json([
                'users' => [
                    'byRole' => $usersByRole,
                    'total' => array_sum(array_column($usersByRole, 'count'))
                ],
                'items' => [
                    'byStatus' => $itemsByStatus,
                    'total' => array_sum(array_column($itemsByStatus, 'count'))
                ],
                'transactions' => [
                    'total' => (int)$totalTransactions
                ],
                'earnings' => [
                    'total' => $totalEarnings
                ]
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete item (Admin or Moderator)
     * DELETE /api/admin/items/{itemId}
     */
    public function deleteItem(int $itemId): void
    {
        $user = RoleMiddleware::requireAdminOrModerator();
        if (!$user) return;

        try {
            $db = Database::getConnection();
            
            // Delete item (cascade will handle related records)
            $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            
            if ($stmt->rowCount() === 0) {
                Response::json(['error' => 'Item not found'], 404);
                return;
            }

            Response::json([
                'message' => 'Item deleted successfully',
                'itemId' => $itemId
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
