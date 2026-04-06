<?php

namespace App\Services;

use App\Config\Database;

class OrderService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get user's orders
     */
    public function getUserOrders(int $userId, ?string $status = null): array
    {
        $sql = "
            SELECT o.*, i.title as itemTitle, i.imageUrl,
                   u.name as sellerName, u.email as sellerEmail,
                   sa.fullName, sa.addressLine1, sa.addressLine2, 
                   sa.city, sa.state, sa.zipCode, sa.country, sa.phone
            FROM orders o
            JOIN items i ON o.itemId = i.id
            JOIN users u ON o.sellerId = u.id
            LEFT JOIN shipping_addresses sa ON o.shippingAddressId = sa.id
            WHERE o.buyerId = :userId
        ";

        $params = ['userId' => $userId];

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
     * Get order details
     */
    public function getOrderDetails(int $userId, int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT o.*, i.title as itemTitle, i.description, i.imageUrl,
                   u.name as sellerName, u.email as sellerEmail,
                   sa.fullName, sa.addressLine1, sa.addressLine2,
                   sa.city, sa.state, sa.zipCode, sa.country, sa.phone,
                   t.amount as paidAmount, t.paymentMethod, t.createdAt as paidAt
            FROM orders o
            JOIN items i ON o.itemId = i.id
            JOIN users u ON o.sellerId = u.id
            LEFT JOIN shipping_addresses sa ON o.shippingAddressId = sa.id
            LEFT JOIN transactions t ON o.itemId = t.itemId AND o.buyerId = t.buyerId
            WHERE o.id = :orderId AND o.buyerId = :userId
        ");
        $stmt->execute(['orderId' => $orderId, 'userId' => $userId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \Exception('Order not found');
        }

        return $order;
    }

    /**
     * Create order after winning auction
     */
    public function createOrder(int $userId, int $itemId, int $shippingAddressId): array
    {
        // Verify user won the auction
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   (SELECT userId FROM bids WHERE itemId = i.id ORDER BY amount DESC LIMIT 1) as winnerId,
                   (SELECT MAX(amount) FROM bids WHERE itemId = i.id) as finalPrice
            FROM items i
            WHERE i.id = :itemId AND i.status = 'ended'
        ");
        $stmt->execute(['itemId' => $itemId]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item) {
            throw new \Exception('Item not found or auction not ended');
        }

        if ($item['winnerId'] != $userId) {
            throw new \Exception('You did not win this auction');
        }

        // Check if order already exists
        $stmt = $this->db->prepare("SELECT id FROM orders WHERE itemId = :itemId AND buyerId = :userId");
        $stmt->execute(['itemId' => $itemId, 'userId' => $userId]);
        if ($stmt->fetch()) {
            throw new \Exception('Order already exists for this item');
        }

        // Verify shipping address belongs to user
        $stmt = $this->db->prepare("SELECT id FROM shipping_addresses WHERE id = :id AND userId = :userId");
        $stmt->execute(['id' => $shippingAddressId, 'userId' => $userId]);
        if (!$stmt->fetch()) {
            throw new \Exception('Invalid shipping address');
        }

        // Calculate shipping
        $shippingService = new ShippingService();
        $shippingCost = $shippingService->calculateShipping($itemId, $shippingAddressId);

        // Create order
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                itemId, buyerId, sellerId, shippingAddressId, 
                totalAmount, shippingCost, status, createdAt
            ) VALUES (
                :itemId, :buyerId, :sellerId, :shippingAddressId,
                :totalAmount, :shippingCost, 'pending_payment', NOW()
            )
        ");

        $totalAmount = $item['finalPrice'] + $shippingCost;

        $stmt->execute([
            'itemId' => $itemId,
            'buyerId' => $userId,
            'sellerId' => $item['sellerId'],
            'shippingAddressId' => $shippingAddressId,
            'totalAmount' => $totalAmount,
            'shippingCost' => $shippingCost
        ]);

        $orderId = $this->db->lastInsertId();

        return [
            'orderId' => $orderId,
            'totalAmount' => $totalAmount,
            'shippingCost' => $shippingCost,
            'itemPrice' => $item['finalPrice'],
            'message' => 'Order created successfully'
        ];
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(int $userId, int $orderId, string $status, ?string $trackingNumber = null): array
    {
        // Verify order belongs to user or user is seller
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE id = :orderId AND (buyerId = :userId OR sellerId = :userId)
        ");
        $stmt->execute(['orderId' => $orderId, 'userId' => $userId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \Exception('Order not found');
        }

        $validStatuses = ['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status');
        }

        $updateFields = ['status = :status'];
        $params = ['orderId' => $orderId, 'status' => $status];

        if ($trackingNumber) {
            $updateFields[] = 'trackingNumber = :trackingNumber';
            $params['trackingNumber'] = $trackingNumber;
        }

        if ($status === 'shipped') {
            $updateFields[] = 'shippedAt = NOW()';
        } elseif ($status === 'delivered') {
            $updateFields[] = 'deliveredAt = NOW()';
        }

        $sql = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = :orderId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Create notification
        $notifyUserId = ($order['buyerId'] == $userId) ? $order['sellerId'] : $order['buyerId'];
        $this->createNotification($notifyUserId, 'order_update', "Order status updated to: $status", $order['itemId']);

        return ['message' => 'Order status updated', 'status' => $status];
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $userId, int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE id = :orderId AND buyerId = :userId AND status = 'pending_payment'
        ");
        $stmt->execute(['orderId' => $orderId, 'userId' => $userId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \Exception('Order not found or cannot be cancelled');
        }

        $stmt = $this->db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :orderId");
        $stmt->execute(['orderId' => $orderId]);

        return ['message' => 'Order cancelled successfully'];
    }

    /**
     * Get items user has won but not yet ordered
     */
    public function getWonItems(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   (SELECT MAX(amount) FROM bids WHERE itemId = i.id) as finalPrice,
                   (SELECT COUNT(*) FROM bids WHERE itemId = i.id) as bidCount
            FROM items i
            WHERE i.status = 'ended'
            AND (SELECT userId FROM bids WHERE itemId = i.id ORDER BY amount DESC LIMIT 1) = :userId
            AND NOT EXISTS (SELECT 1 FROM orders WHERE itemId = i.id AND buyerId = :userId)
            ORDER BY i.endTime DESC
        ");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
