<?php

namespace App\Services;

use PDO;
use App\Models\Watchlist;

class WatchlistService {
    private PDO $db;
    private Watchlist $watchlistModel;
    private const NOTIFICATION_THRESHOLD_HOURS = 24;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->watchlistModel = new Watchlist($db);
    }

    /**
     * Check if a watchlist entry already exists
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @return bool True if duplicate exists
     */
    private function isDuplicate(int $userId, int $itemId): bool {
        return $this->watchlistModel->exists($userId, $itemId);
    }

    /**
     * Add an item to user's watchlist
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @return bool True on success
     * @throws \Exception If duplicate entry or item doesn't exist
     */
    public function addToWatchlist(int $userId, int $itemId): bool {
        // Check for duplicates
        if ($this->isDuplicate($userId, $itemId)) {
            throw new \Exception('Item already in watchlist');
        }

        // Verify item exists
        $stmt = $this->db->prepare("SELECT id FROM items WHERE id = :item_id");
        $stmt->execute([':item_id' => $itemId]);
        if (!$stmt->fetch()) {
            throw new \Exception('Item not found');
        }

        // Create watchlist entry
        $watchlistId = $this->watchlistModel->create($userId, $itemId);
        return $watchlistId > 0;
    }

    /**
     * Remove an item from user's watchlist
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @return bool True on success
     */
    public function removeFromWatchlist(int $userId, int $itemId): bool {
        return $this->watchlistModel->delete($userId, $itemId);
    }

    /**
     * Check if user is watching a specific item
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @return bool True if watching
     */
    public function isWatching(int $userId, int $itemId): bool {
        return $this->watchlistModel->exists($userId, $itemId);
    }

    /**
     * Get user's watchlist with item details
     * 
     * @param int $userId User ID
     * @return array List of watchlist items with details
     */
    public function getWatchlist(int $userId): array {
        $watchlistItems = $this->watchlistModel->findByUserId($userId);
        
        $result = [];
        foreach ($watchlistItems as $item) {
            $result[] = $this->watchlistModel->toArray($item);
        }
        
        return $result;
    }

    /**
     * Get watched items ending soon for a user
     * 
     * @param int $userId User ID
     * @param int $hoursThreshold Hours threshold (default 24)
     * @return array List of items ending within threshold
     */
    public function getEndingSoonItems(int $userId, int $hoursThreshold = self::NOTIFICATION_THRESHOLD_HOURS): array {
        $stmt = $this->db->prepare("
            SELECT w.watchlist_id, w.user_id, w.item_id, w.added_at,
                   i.id, i.title, i.description, i.current_price, i.end_time, i.status,
                   i.seller_id, u.name as seller_name
            FROM watchlist w
            JOIN items i ON w.item_id = i.id
            JOIN users u ON i.seller_id = u.id
            WHERE w.user_id = :user_id
              AND i.status = 'active'
              AND i.end_time > NOW()
              AND i.end_time <= DATE_ADD(NOW(), INTERVAL :hours HOUR)
            ORDER BY i.end_time ASC
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':hours' => $hoursThreshold
        ]);
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'watchlistId' => (int) $item['watchlist_id'],
                'userId' => (int) $item['user_id'],
                'itemId' => (int) $item['item_id'],
                'addedAt' => $item['added_at'],
                'item' => [
                    'id' => (int) $item['id'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'currentPrice' => (float) $item['current_price'],
                    'endTime' => $item['end_time'],
                    'status' => $item['status'],
                    'sellerId' => (int) $item['seller_id'],
                    'sellerName' => $item['seller_name']
                ]
            ];
        }
        
        return $result;
    }
}
