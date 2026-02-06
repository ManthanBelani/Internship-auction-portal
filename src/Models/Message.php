<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Message
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new message
     */
    public function create(int $senderId, int $receiverId, ?int $itemId, string $content): array
    {
        $sql = "INSERT INTO messages (sender_id, receiver_id, item_id, message) 
                VALUES (:sender_id, :receiver_id, :item_id, :message)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sender_id' => $senderId,
            ':receiver_id' => $receiverId,
            ':item_id' => $itemId,
            ':message' => $content
        ]);

        $id = $this->db->lastInsertId();
        return $this->findById((int)$id);
    }

    /**
     * Find message by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT m.*, u1.name as sender_name, u2.name as receiver_name 
                FROM messages m
                JOIN users u1 ON m.sender_id = u1.id
                JOIN users u2 ON m.receiver_id = u2.id
                WHERE m.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    /**
     * Get conversation between two users
     */
    public function getConversation(int $userId1, int $userId2, ?int $itemId = null): array
    {
        $sql = "SELECT m.*, u1.name as sender_name 
                FROM messages m
                JOIN users u1 ON m.sender_id = u1.id
                WHERE ((sender_id = :u1 AND receiver_id = :u2) OR (sender_id = :u2 AND receiver_id = :u1))";
        
        $params = [':u1' => $userId1, ':u2' => $userId2];
        
        if ($itemId) {
            $sql .= " AND item_id = :item_id";
            $params[':item_id'] = $itemId;
        }

        $sql .= " ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(int $receiverId, int $senderId, ?int $itemId = null): void
    {
        $sql = "UPDATE messages SET is_read = 1 
                WHERE receiver_id = :receiver_id AND sender_id = :sender_id AND is_read = 0";
        
        $params = [':receiver_id' => $receiverId, ':sender_id' => $senderId];
        
        if ($itemId) {
            $sql .= " AND item_id = :item_id";
            $params[':item_id'] = $itemId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Get recent conversations for a user
     */
    public function getRecentConversations(int $userId): array
    {
        // This is a bit complex in SQLite/MySQL to get the "latest message per pair"
        $sql = "SELECT m1.*, u.name as other_party_name, i.title as item_title
                FROM messages m1
                JOIN (
                    SELECT 
                        CASE WHEN sender_id = :user_id THEN receiver_id ELSE sender_id END as other_party,
                        MAX(created_at) as max_date
                    FROM messages
                    WHERE sender_id = :user_id OR receiver_id = :user_id
                    GROUP BY other_party
                ) m2 ON (CASE WHEN m1.sender_id = :user_id THEN m1.receiver_id ELSE m1.sender_id END = m2.other_party AND m1.created_at = m2.max_date)
                JOIN users u ON (CASE WHEN m1.sender_id = :user_id THEN m1.receiver_id ELSE m1.sender_id END = u.id)
                LEFT JOIN items i ON m1.item_id = i.id
                WHERE m1.sender_id = :user_id OR m1.receiver_id = :user_id
                ORDER BY m1.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
