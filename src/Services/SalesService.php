<?php

namespace App\Services;

use App\Config\Database;

class SalesService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all sales for a seller
     */
    public function getSellerSales(int $sellerId, ?string $status = null): array
    {
        $sql = "
            SELECT o.*, i.title as itemTitle, i.imageUrl,
                   u.name as buyerName, u.email as buyerEmail,
                   t.amount as saleAmount, t.commission, t.sellerAmount,
                   sa.fullName, sa.addressLine1, sa.city, sa.state, sa.zipCode
            FROM orders o
            JOIN items i ON o.itemId = i.id
            JOIN users u ON o.buyerId = u.id
            LEFT JOIN transactions t ON o.itemId = t.itemId AND o.buyerId = t.buyerId
            LEFT JOIN shipping_addresses sa ON o.shippingAddressId = sa.id
            WHERE o.sellerId = :sellerId
        ";

        $params = ['sellerId' => $sellerId];

        if ($status) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY o.createdAt DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get sale details
     */
    public function getSaleDetails(int $sellerId, int $saleId): array
    {
        $stmt = $this->db->prepare("
            SELECT o.*, i.title as itemTitle, i.description, i.imageUrl,
                   u.name as buyerName, u.email as buyerEmail, u.id as buyerId,
                   t.amount as saleAmount, t.commission, t.sellerAmount, t.paymentMethod,
                   sa.fullName, sa.addressLine1, sa.addressLine2, 
                   sa.city, sa.state, sa.zipCode, sa.country, sa.phone
            FROM orders o
            JOIN items i ON o.itemId = i.id
            JOIN users u ON o.buyerId = u.id
            LEFT JOIN transactions t ON o.itemId = t.itemId AND o.buyerId = t.buyerId
            LEFT JOIN shipping_addresses sa ON o.shippingAddressId = sa.id
            WHERE o.id = :saleId AND o.sellerId = :sellerId
        ");
        $stmt->execute(['saleId' => $saleId, 'sellerId' => $sellerId]);
        $sale = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sale) {
            throw new \Exception('Sale not found');
        }

        return $sale;
    }

    /**
     * Mark sale as shipped
     */
    public function markAsShipped(int $sellerId, int $saleId, string $trackingNumber, ?string $carrier = null): array
    {
        // Verify ownership
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :saleId AND sellerId = :sellerId");
        $stmt->execute(['saleId' => $saleId, 'sellerId' => $sellerId]);
        $sale = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sale) {
            throw new \Exception('Sale not found');
        }

        if ($sale['status'] !== 'paid') {
            throw new \Exception('Can only ship paid orders');
        }

        // Update order
        $stmt = $this->db->prepare("
            UPDATE orders 
            SET status = 'shipped', 
                trackingNumber = :trackingNumber,
                shippedAt = NOW()
            WHERE id = :saleId
        ");
        $stmt->execute([
            'saleId' => $saleId,
            'trackingNumber' => $trackingNumber
        ]);

        // Create notification for buyer
        $this->createNotification(
            $sale['buyerId'],
            'order_shipped',
            "Your order has been shipped! Tracking: $trackingNumber",
            $sale['itemId']
        );

        return [
            'message' => 'Order marked as shipped',
            'trackingNumber' => $trackingNumber
        ];
    }

    /**
     * Mark sale as delivered
     */
    public function markAsDelivered(int $sellerId, int $saleId): array
    {
        // Verify ownership
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :saleId AND sellerId = :sellerId");
        $stmt->execute(['saleId' => $saleId, 'sellerId' => $sellerId]);
        $sale = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sale) {
            throw new \Exception('Sale not found');
        }

        if ($sale['status'] !== 'shipped') {
            throw new \Exception('Can only mark shipped orders as delivered');
        }

        // Update order
        $stmt = $this->db->prepare("
            UPDATE orders 
            SET status = 'delivered',
                deliveredAt = NOW()
            WHERE id = :saleId
        ");
        $stmt->execute(['saleId' => $saleId]);

        // Create notification for buyer
        $this->createNotification(
            $sale['buyerId'],
            'order_delivered',
            "Your order has been delivered!",
            $sale['itemId']
        );

        return ['message' => 'Order marked as delivered'];
    }

    /**
     * Get revenue data
     */
    public function getRevenue(int $sellerId, string $period = 'month'): array
    {
        $dateFormat = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%W',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(t.createdAt, :dateFormat) as period,
                COUNT(*) as salesCount,
                SUM(t.amount) as totalRevenue,
                SUM(t.sellerAmount) as sellerRevenue,
                SUM(t.commission) as totalCommission,
                AVG(t.amount) as avgSaleAmount
            FROM transactions t
            JOIN orders o ON t.itemId = o.itemId AND t.buyerId = o.buyerId
            WHERE t.sellerId = :sellerId AND t.status = 'completed'
            GROUP BY period
            ORDER BY period DESC
            LIMIT 30
        ");
        $stmt->execute([
            'sellerId' => $sellerId,
            'dateFormat' => $dateFormat
        ]);

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get totals
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as totalSales,
                SUM(t.amount) as totalRevenue,
                SUM(t.sellerAmount) as totalEarnings,
                SUM(t.commission) as totalCommission
            FROM transactions t
            WHERE t.sellerId = :sellerId AND t.status = 'completed'
        ");
        $stmt->execute(['sellerId' => $sellerId]);
        $totals = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'period' => $period,
            'data' => $data,
            'totals' => $totals
        ];
    }

    /**
     * Create notification helper
     */
    private function createNotification(int $userId, string $type, string $message, int $itemId = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (userId, type, message, itemId, isRead, createdAt)
            VALUES (:userId, :type, :message, :itemId, 0, NOW())
        ");
        $stmt->execute([
            'userId' => $userId,
            'type' => $type,
            'message' => $message,
            'itemId' => $itemId
        ]);
    }
}
