<?php

namespace App\Models;

use PDO;

class Watchlist {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create(int $userId, int $itemId): int {
        $stmt = $this->db->prepare("
            INSERT INTO watchlist (user_id, item_id)
            VALUES (:user_id, :item_id)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':item_id' => $itemId
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT w.watchlist_id, w.user_id, w.item_id, w.added_at,
                   i.title, i.description, i.current_price, i.end_time, i.status
            FROM watchlist w
            JOIN items i ON w.item_id = i.id
            WHERE w.user_id = :user_id
            ORDER BY w.added_at DESC
        ");
        
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $userId, int $itemId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM watchlist
            WHERE user_id = :user_id AND item_id = :item_id
        ");
        
        return $stmt->execute([
            ':user_id' => $userId,
            ':item_id' => $itemId
        ]);
    }

    public function exists(int $userId, int $itemId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM watchlist
            WHERE user_id = :user_id AND item_id = :item_id
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':item_id' => $itemId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    public function toArray(array $data): array {
        return [
            'watchlistId' => (int) $data['watchlist_id'],
            'userId' => (int) $data['user_id'],
            'itemId' => (int) $data['item_id'],
            'addedAt' => $data['added_at'],
            'item' => isset($data['title']) ? [
                'title' => $data['title'],
                'description' => $data['description'],
                'currentPrice' => (float) $data['current_price'],
                'endTime' => $data['end_time'],
                'status' => $data['status']
            ] : null
        ];
    }
}
