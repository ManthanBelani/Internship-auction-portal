<?php

namespace App\Services;

use PDO;

class NotificationQueueService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Queue a notification for later delivery
     * 
     * @param int $userId User ID to receive notification
     * @param string $type Notification type (bid_update, outbid, auction_ending, auction_ended)
     * @param int|null $itemId Related item ID
     * @param array $payload Notification data
     * @return int Notification ID
     */
    public function queueNotification(int $userId, string $type, ?int $itemId, array $payload): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, item_id, notification_type, payload)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $itemId,
            $type,
            json_encode($payload)
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get pending notifications for a user
     * 
     * @param int $userId User ID
     * @return array List of pending notifications
     */
    public function getPendingNotifications(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT notification_id, item_id, notification_type, payload, created_at
            FROM notifications
            WHERE user_id = ? AND delivered = FALSE
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        
        return array_map(function($notification) {
            return [
                'notificationId' => (int)$notification['notification_id'],
                'itemId' => $notification['item_id'] ? (int)$notification['item_id'] : null,
                'type' => $notification['notification_type'],
                'payload' => json_decode($notification['payload'], true),
                'createdAt' => $notification['created_at']
            ];
        }, $notifications);
    }

    /**
     * Mark notification as delivered
     * 
     * @param int $notificationId Notification ID
     * @return bool Success status
     */
    public function markAsDelivered(int $notificationId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET delivered = TRUE, delivered_at = NOW()
            WHERE notification_id = ?
        ");
        
        return $stmt->execute([$notificationId]);
    }

    /**
     * Mark multiple notifications as delivered
     * 
     * @param array $notificationIds Array of notification IDs
     * @return bool Success status
     */
    public function markMultipleAsDelivered(array $notificationIds): bool
    {
        if (empty($notificationIds)) {
            return true;
        }
        
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET delivered = TRUE, delivered_at = NOW()
            WHERE notification_id IN ($placeholders)
        ");
        
        return $stmt->execute($notificationIds);
    }

    /**
     * Delete old delivered notifications
     * 
     * @param int $hoursOld Delete notifications older than this many hours (default 24)
     * @return int Number of notifications deleted
     */
    public function cleanupOldNotifications(int $hoursOld = 24): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM notifications
            WHERE delivered = TRUE
            AND delivered_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        
        $stmt->execute([$hoursOld]);
        return $stmt->rowCount();
    }

    /**
     * Get notification count for a user
     * 
     * @param int $userId User ID
     * @param bool $deliveredOnly Count only delivered notifications
     * @return int Notification count
     */
    public function getNotificationCount(int $userId, bool $deliveredOnly = false): int
    {
        if ($deliveredOnly) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM notifications
                WHERE user_id = ? AND delivered = TRUE
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM notifications
                WHERE user_id = ? AND delivered = FALSE
            ");
        }
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }

    /**
     * Batch notifications by type and item
     * Groups multiple rapid notifications for the same item into a single notification
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @param string $type Notification type
     * @param int $withinSeconds Only batch notifications within this time window (default 30)
     * @return array Batched notification data
     */
    public function batchNotifications(int $userId, int $itemId, string $type, int $withinSeconds = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT notification_id, payload, created_at
            FROM notifications
            WHERE user_id = ?
            AND item_id = ?
            AND notification_type = ?
            AND delivered = FALSE
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$userId, $itemId, $type, $withinSeconds]);
        $notifications = $stmt->fetchAll();
        
        if (empty($notifications)) {
            return [];
        }
        
        // Get the most recent notification
        $latest = $notifications[0];
        $notificationIds = array_column($notifications, 'notification_id');
        
        return [
            'notificationIds' => $notificationIds,
            'count' => count($notifications),
            'latestPayload' => json_decode($latest['payload'], true),
            'createdAt' => $latest['created_at']
        ];
    }
}
