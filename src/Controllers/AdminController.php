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
    public function getAllUsers(): void
    {
        $queryParams = $_GET;
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

    /**
     * Get all payout requests (Admin only)
     * GET /api/admin/payouts
     */
    public function getPayouts(): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $db = Database::getConnection();
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT p.*, u.name as seller_name, u.email as seller_email 
                    FROM payouts p 
                    JOIN users u ON p.seller_id = u.id";
            
            if ($status) {
                $sql .= " WHERE p.status = :status";
            }
            
            $sql .= " ORDER BY p.requested_at DESC";
            
            $stmt = $db->prepare($sql);
            if ($status) {
                $stmt->execute([':status' => $status]);
            } else {
                $stmt->execute();
            }
            
            $payouts = $stmt->fetchAll();
            Response::json(['payouts' => $payouts]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update payout status (Admin only)
     * PUT /api/admin/payouts/{id}
     */
    public function updatePayoutStatus(int $id, array $data): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            if (!isset($data['status'])) {
                Response::json(['error' => 'Status is required'], 400);
                return;
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE payouts SET status = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$data['status'], $id]);

            Response::json(['message' => 'Payout status updated successfully']);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get all transactions (Admin only)
     * GET /api/admin/transactions
     */
    public function getTransactions(): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $db = Database::getConnection();
            $queryParams = $_GET;
            
            $sql = "SELECT t.*, 
                    buyer.name as buyer_name, buyer.email as buyer_email,
                    seller.name as seller_name, seller.email as seller_email,
                    i.title as item_title, i.image_url
                    FROM transactions t
                    LEFT JOIN users buyer ON t.buyer_id = buyer.id
                    LEFT JOIN users seller ON t.seller_id = seller.id
                    LEFT JOIN items i ON t.item_id = i.id
                    WHERE 1=1";
            
            $params = [];
            
            if (isset($queryParams['status']) && $queryParams['status']) {
                $sql .= " AND t.status = ?";
                $params[] = $queryParams['status'];
            }
            
            if (isset($queryParams['search']) && $queryParams['search']) {
                $sql .= " AND (i.title LIKE ? OR buyer.name LIKE ? OR seller.name LIKE ?)";
                $searchTerm = '%' . $queryParams['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (isset($queryParams['dateFrom']) && $queryParams['dateFrom']) {
                $sql .= " AND DATE(t.created_at) >= ?";
                $params[] = $queryParams['dateFrom'];
            }
            
            if (isset($queryParams['dateTo']) && $queryParams['dateTo']) {
                $sql .= " AND DATE(t.created_at) <= ?";
                $params[] = $queryParams['dateTo'];
            }
            
            $sql .= " ORDER BY t.created_at DESC LIMIT 100";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll();
            
            Response::json([
                'transactions' => $transactions,
                'total' => count($transactions)
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all reviews (Admin only)
     * GET /api/admin/reviews
     */
    public function getReviews(): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $db = Database::getConnection();
            $queryParams = $_GET;
            
            $sql = "SELECT r.*, 
                    reviewer.name as reviewer_name,
                    reviewee.name as reviewee_name
                    FROM reviews r
                    LEFT JOIN users reviewer ON r.reviewer_id = reviewer.id
                    LEFT JOIN users reviewee ON r.reviewee_id = reviewee.id
                    WHERE 1=1";
            
            $params = [];
            
            if (isset($queryParams['rating']) && $queryParams['rating']) {
                $sql .= " AND r.rating = ?";
                $params[] = (int)$queryParams['rating'];
            }
            
            if (isset($queryParams['type']) && $queryParams['type']) {
                $sql .= " AND r.type = ?";
                $params[] = $queryParams['type'];
            }
            
            if (isset($queryParams['search']) && $queryParams['search']) {
                $sql .= " AND r.comment LIKE ?";
                $params[] = '%' . $queryParams['search'] . '%';
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT 100";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $reviews = $stmt->fetchAll();
            
            Response::json([
                'reviews' => $reviews,
                'total' => count($reviews)
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete review (Admin only)
     * DELETE /api/admin/reviews/{reviewId}
     */
    public function deleteReview(int $reviewId): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            
            if ($stmt->rowCount() === 0) {
                Response::json(['error' => 'Review not found'], 404);
                return;
            }

            Response::json(['message' => 'Review deleted successfully']);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get platform earnings (Admin only)
     * GET /api/admin/earnings
     */
    public function getEarnings(): void
    {
        $admin = RoleMiddleware::requireAdmin();
        if (!$admin) return;

        try {
            $db = Database::getConnection();
            $queryParams = $_GET;
            
            $period = $queryParams['period'] ?? '30'; // days
            $startDate = date('Y-m-d', strtotime("-{$period} days"));
            
            // Total earnings
            $stmt = $db->query("SELECT COALESCE(SUM(commission_amount), 0) as total FROM transactions WHERE status = 'completed'");
            $totalEarnings = $stmt->fetch()['total'];
            
            // Earnings in period
            $stmt = $db->prepare("SELECT COALESCE(SUM(commission_amount), 0) as total FROM transactions WHERE status = 'completed' AND created_at >= ?");
            $stmt->execute([$startDate]);
            $periodEarnings = $stmt->fetch()['total'];
            
            // Today's earnings
            $stmt = $db->query("SELECT COALESCE(SUM(commission_amount), 0) as total FROM transactions WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
            $todayEarnings = $stmt->fetch()['total'];
            
            // Transaction details in period
            $stmt = $db->prepare("SELECT t.*, i.title as item_title, buyer.name as buyer_name, seller.name as seller_name
                    FROM transactions t
                    LEFT JOIN items i ON t.item_id = i.id
                    LEFT JOIN users buyer ON t.buyer_id = buyer.id
                    LEFT JOIN users seller ON t.seller_id = seller.id
                    WHERE t.status = 'completed' AND t.created_at >= ?
                    ORDER BY t.created_at DESC
                    LIMIT 50");
            $stmt->execute([$startDate]);
            $transactions = $stmt->fetchAll();
            
            Response::json([
                'total' => (float)$totalEarnings,
                'period' => (float)$periodEarnings,
                'today' => (float)$todayEarnings,
                'period_days' => (int)$period,
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
