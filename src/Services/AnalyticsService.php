<?php

namespace App\Services;

use App\Config\Database;

class AnalyticsService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get analytics overview
     */
    public function getOverview(int $sellerId): array
    {
        // Total revenue
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as totalSales,
                SUM(amount) as totalRevenue,
                SUM(sellerAmount) as totalEarnings,
                AVG(amount) as avgSalePrice
            FROM transactions
            WHERE sellerId = :sellerId AND status = 'completed'
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $revenue = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Active listings
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM items
            WHERE sellerId = :sellerId AND status = 'active'
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $activeListings = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Total bids received
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM bids b
            JOIN items i ON b.itemId = i.id
            WHERE i.sellerId = :sellerId
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $totalBids = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Pending orders
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM orders
            WHERE sellerId = :sellerId AND status IN ('paid', 'shipped')
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $pendingOrders = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // This month's revenue
        $stmt = $this->db->prepare("
            SELECT SUM(sellerAmount) as amount
            FROM transactions
            WHERE sellerId = :sellerId 
            AND status = 'completed'
            AND DATE_FORMAT(createdAt, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $thisMonthRevenue = $stmt->fetch(\PDO::FETCH_ASSOC)['amount'] ?? 0;

        return [
            'totalSales' => (int)$revenue['totalSales'],
            'totalRevenue' => (float)$revenue['totalRevenue'],
            'totalEarnings' => (float)$revenue['totalEarnings'],
            'avgSalePrice' => (float)$revenue['avgSalePrice'],
            'activeListings' => (int)$activeListings,
            'totalBids' => (int)$totalBids,
            'pendingOrders' => (int)$pendingOrders,
            'thisMonthRevenue' => (float)$thisMonthRevenue
        ];
    }

    /**
     * Get revenue analytics by period
     */
    public function getRevenueAnalytics(int $sellerId, string $period = 'month'): array
    {
        $dateFormat = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%W',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        $limit = match($period) {
            'day' => 30,
            'week' => 12,
            'month' => 12,
            'year' => 5,
            default => 12
        };

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(createdAt, :dateFormat) as period,
                COUNT(*) as salesCount,
                SUM(amount) as revenue,
                SUM(sellerAmount) as earnings,
                AVG(amount) as avgSale
            FROM transactions
            WHERE sellerId = :sellerId AND status = 'completed'
            GROUP BY period
            ORDER BY period DESC
            LIMIT :limit
        ");
        $stmt->execute([
            'sellerId' => $sellerId,
            'dateFormat' => $dateFormat,
            'limit' => $limit
        ]);

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Reverse to show oldest first
        return array_reverse($data);
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(int $sellerId): array
    {
        // Conversion rate (sold items / total items)
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as totalItems,
                SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as soldItems
            FROM items
            WHERE sellerId = :sellerId
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $items = $stmt->fetch(\PDO::FETCH_ASSOC);
        $conversionRate = $items['totalItems'] > 0 
            ? ($items['soldItems'] / $items['totalItems']) * 100 
            : 0;

        // Average time to sell
        $stmt = $this->db->prepare("
            SELECT AVG(DATEDIFF(endTime, createdAt)) as avgDays
            FROM items
            WHERE sellerId = :sellerId AND status = 'sold'
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $avgTimeToSell = $stmt->fetch(\PDO::FETCH_ASSOC)['avgDays'] ?? 0;

        // Success rate (items with bids / total items)
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT i.id) as itemsWithBids
            FROM items i
            JOIN bids b ON i.id = b.itemId
            WHERE i.sellerId = :sellerId
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $itemsWithBids = $stmt->fetch(\PDO::FETCH_ASSOC)['itemsWithBids'];
        $successRate = $items['totalItems'] > 0 
            ? ($itemsWithBids / $items['totalItems']) * 100 
            : 0;

        // Average bids per item
        $stmt = $this->db->prepare("
            SELECT AVG(bidCount) as avgBids
            FROM (
                SELECT COUNT(*) as bidCount
                FROM bids b
                JOIN items i ON b.itemId = i.id
                WHERE i.sellerId = :sellerId
                GROUP BY b.itemId
            )
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $avgBidsPerItem = $stmt->fetch(\PDO::FETCH_ASSOC)['avgBids'] ?? 0;

        return [
            'conversionRate' => round($conversionRate, 2),
            'avgTimeToSell' => round($avgTimeToSell, 1),
            'successRate' => round($successRate, 2),
            'avgBidsPerItem' => round($avgBidsPerItem, 1),
            'totalListings' => (int)$items['totalItems'],
            'soldItems' => (int)$items['soldItems']
        ];
    }

    /**
     * Get category performance
     */
    public function getCategoryPerformance(int $sellerId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                i.category,
                COUNT(*) as itemCount,
                SUM(CASE WHEN i.status = 'sold' THEN 1 ELSE 0 END) as soldCount,
                AVG(t.amount) as avgPrice,
                SUM(t.sellerAmount) as totalRevenue
            FROM items i
            LEFT JOIN transactions t ON i.id = t.itemId AND t.status = 'completed'
            WHERE i.sellerId = :sellerId
            GROUP BY i.category
            ORDER BY totalRevenue DESC
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
