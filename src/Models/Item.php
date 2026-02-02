<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;
use DateTime;

class Item
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new auction item
     * 
     * @param int $sellerId Seller user ID
     * @param string $title Item title
     * @param string $description Item description
     * @param float $startingPrice Starting price (must be positive)
     * @param string $endTime Auction end time (must be in future)
     * @return array Created item data
     * @throws \Exception If validation fails
     */
    public function create(int $sellerId, string $title, string $description, float $startingPrice, string $endTime): array
    {
        // Validate positive price
        if ($startingPrice <= 0) {
            throw new \Exception('Starting price must be positive');
        }

        // Validate future end time
        $endDateTime = new DateTime($endTime);
        $now = new DateTime();
        
        if ($endDateTime <= $now) {
            throw new \Exception('End time must be in the future');
        }

        // Validate required fields
        if (empty($title) || empty($description)) {
            throw new \Exception('Title and description are required');
        }

        try {
            $sql = "INSERT INTO items (title, description, starting_price, current_price, end_time, seller_id, status) 
                    VALUES (:title, :description, :starting_price, :current_price, :end_time, :seller_id, 'active')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':starting_price' => $startingPrice,
                ':current_price' => $startingPrice,
                ':end_time' => $endTime,
                ':seller_id' => $sellerId
            ]);

            $itemId = (int)$this->db->lastInsertId();
            
            return $this->findById($itemId);
        } catch (PDOException $e) {
            throw new \Exception('Failed to create item: ' . $e->getMessage());
        }
    }

    /**
     * Find item by ID
     * 
     * @param int $itemId Item ID
     * @return array Item data with seller information
     * @throws \Exception If item not found
     */
    public function findById(int $itemId): array
    {
        $sql = "SELECT i.*, u.name as seller_name 
                FROM items i 
                JOIN users u ON i.seller_id = u.id 
                WHERE i.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $itemId]);
        
        $item = $stmt->fetch();
        
        if (!$item) {
            throw new \Exception('Item not found');
        }
        
        return $item;
    }

    /**
     * Find all active items (status='active' and end_time in future)
     * 
     * @param array $filters Optional filters (search, sellerId)
     * @return array List of active items
     */
    public function findActive(array $filters = []): array
    {
        $sql = "SELECT i.*, u.name as seller_name 
                FROM items i 
                JOIN users u ON i.seller_id = u.id 
                WHERE i.status = 'active' AND i.end_time > NOW()";
        
        $params = [];

        // Add seller filter
        if (isset($filters['sellerId'])) {
            $sql .= " AND i.seller_id = :seller_id";
            $params[':seller_id'] = $filters['sellerId'];
        }

        // Add search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= " AND (i.title LIKE :search OR i.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Search items by keyword (title or description)
     * 
     * @param string $keyword Search keyword
     * @return array List of matching items
     */
    public function search(string $keyword): array
    {
        return $this->findActive(['search' => $keyword]);
    }

    /**
     * Update item
     * 
     * @param int $itemId Item ID
     * @param array $data Data to update
     * @return array Updated item data
     * @throws \Exception If item not found or validation fails
     */
    public function update(int $itemId, array $data): array
    {
        // Verify item exists
        $item = $this->findById($itemId);

        $updates = [];
        $params = [':id' => $itemId];

        if (isset($data['current_price'])) {
            if ($data['current_price'] <= 0) {
                throw new \Exception('Current price must be positive');
            }
            $updates[] = "current_price = :current_price";
            $params[':current_price'] = $data['current_price'];
        }

        if (isset($data['highest_bidder_id'])) {
            $updates[] = "highest_bidder_id = :highest_bidder_id";
            $params[':highest_bidder_id'] = $data['highest_bidder_id'];
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'completed', 'expired'])) {
                throw new \Exception('Invalid status');
            }
            $updates[] = "status = :status";
            $params[':status'] = $data['status'];
        }

        if (isset($data['reserve_price'])) {
            if ($data['reserve_price'] !== null && $data['reserve_price'] <= 0) {
                throw new \Exception('Reserve price must be positive');
            }
            $updates[] = "reserve_price = :reserve_price";
            $params[':reserve_price'] = $data['reserve_price'];
        }

        if (isset($data['reserve_met'])) {
            $updates[] = "reserve_met = :reserve_met";
            $params[':reserve_met'] = $data['reserve_met'] ? 1 : 0;
        }

        if (empty($updates)) {
            return $item;
        }

        try {
            $sql = "UPDATE items SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->findById($itemId);
        } catch (PDOException $e) {
            throw new \Exception('Failed to update item: ' . $e->getMessage());
        }
    }

    /**
     * Find expired auctions that need to be completed
     * 
     * @return array List of expired items
     */
    public function findExpired(): array
    {
        $sql = "SELECT * FROM items 
                WHERE status = 'active' AND end_time <= NOW()";
        
        $stmt = $this->db->query($sql);
        
        return $stmt->fetchAll();
    }

    /**
     * Convert item data to array format with optional seller-specific fields
     * 
     * @param array $item Raw item data from database
     * @param bool $isSeller Whether the requesting user is the seller
     * @return array Formatted item data
     */
    public function toArray(array $item, bool $isSeller = false): array
    {
        $result = [
            'itemId' => (int)$item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'startingPrice' => (float)$item['starting_price'],
            'currentPrice' => (float)$item['current_price'],
            'endTime' => $item['end_time'],
            'sellerId' => (int)$item['seller_id'],
            'status' => $item['status'],
            'createdAt' => $item['created_at']
        ];

        // Add seller name if available
        if (isset($item['seller_name'])) {
            $result['sellerName'] = $item['seller_name'];
        }

        // Add highest bidder if exists
        if (isset($item['highest_bidder_id']) && $item['highest_bidder_id']) {
            $result['highestBidderId'] = (int)$item['highest_bidder_id'];
        }

        // Add reserve price only for seller
        if ($isSeller && isset($item['reserve_price']) && $item['reserve_price'] !== null) {
            $result['reservePrice'] = (float)$item['reserve_price'];
        }

        // Add reserve met status for everyone if reserve is set
        if (isset($item['reserve_price']) && $item['reserve_price'] !== null) {
            $result['reserveMet'] = isset($item['reserve_met']) ? (bool)$item['reserve_met'] : false;
        }

        return $result;
    }
}
