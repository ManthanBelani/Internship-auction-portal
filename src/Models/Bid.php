<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Bid
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new bid
     * 
     * @param int $itemId Item ID
     * @param int $bidderId Bidder user ID
     * @param float $amount Bid amount (must be positive)
     * @return array Created bid data
     * @throws \Exception If validation fails
     */
    public function create(int $itemId, int $bidderId, float $amount): array
    {
        // Validate positive amount
        if ($amount <= 0) {
            throw new \Exception('Bid amount must be positive');
        }

        try {
            $sql = "INSERT INTO bids (item_id, bidder_id, amount, timestamp) 
                    VALUES (:item_id, :bidder_id, :amount, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':item_id' => $itemId,
                ':bidder_id' => $bidderId,
                ':amount' => $amount
            ]);

            $bidId = (int)$this->db->lastInsertId();
            
            return $this->findById($bidId);
        } catch (PDOException $e) {
            throw new \Exception('Failed to create bid: ' . $e->getMessage());
        }
    }

    /**
     * Find bid by ID
     * 
     * @param int $bidId Bid ID
     * @return array Bid data
     * @throws \Exception If bid not found
     */
    public function findById(int $bidId): array
    {
        $sql = "SELECT b.*, u.name as bidder_name 
                FROM bids b 
                JOIN users u ON b.bidder_id = u.id 
                WHERE b.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $bidId]);
        
        $bid = $stmt->fetch();
        
        if (!$bid) {
            throw new \Exception('Bid not found');
        }
        
        return $bid;
    }

    /**
     * Find all bids for a specific item
     * 
     * @param int $itemId Item ID
     * @return array List of bids ordered by timestamp (descending)
     */
    public function findByItemId(int $itemId): array
    {
        $sql = "SELECT b.*, u.name as bidder_name 
                FROM bids b 
                JOIN users u ON b.bidder_id = u.id 
                WHERE b.item_id = :item_id 
                ORDER BY b.timestamp DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Find all bids by a specific bidder
     * 
     * @param int $bidderId Bidder user ID
     * @return array List of bids ordered by timestamp (descending)
     */
    public function findByBidderId(int $bidderId): array
    {
        $sql = "SELECT b.*, i.title as item_title 
                FROM bids b 
                JOIN items i ON b.item_id = i.id 
                WHERE b.bidder_id = :bidder_id 
                ORDER BY b.timestamp DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':bidder_id' => $bidderId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Find unique items a bidder has participated in
     */
    public function findItemsByBidderId(int $bidderId): array
    {
        $sql = "SELECT i.*, u.name as seller_name, MAX(b.amount) as my_highest_bid, MAX(b.timestamp) as my_last_bid_at
                FROM items i
                JOIN bids b ON i.id = b.item_id
                JOIN users u ON i.seller_id = u.id
                WHERE b.bidder_id = :bidder_id
                GROUP BY i.id
                ORDER BY my_last_bid_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':bidder_id' => $bidderId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get the highest bid for an item
     * 
     * @param int $itemId Item ID
     * @return array|null Highest bid data or null if no bids
     */
    public function getHighestBid(int $itemId): ?array
    {
        $sql = "SELECT b.*, u.name as bidder_name 
                FROM bids b 
                JOIN users u ON b.bidder_id = u.id 
                WHERE b.item_id = :item_id 
                ORDER BY b.amount DESC, b.timestamp ASC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        
        $bid = $stmt->fetch();
        
        return $bid ?: null;
    }

    /**
     * Count bids for an item
     * 
     * @param int $itemId Item ID
     * @return int Number of bids
     */
    public function countByItemId(int $itemId): int
    {
        $sql = "SELECT COUNT(*) as count FROM bids WHERE item_id = :item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
}
