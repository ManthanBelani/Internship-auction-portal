<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Transaction
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new transaction
     * 
     * @param int $itemId Item ID
     * @param int $sellerId Seller user ID
     * @param int $buyerId Buyer user ID
     * @param float $finalPrice Final transaction price (must be positive)
     * @param float $commissionAmount Commission amount to be deducted
     * @param float $sellerPayout Amount paid to seller after commission
     * @return array Created transaction data
     * @throws \Exception If validation fails
     */
    public function create(int $itemId, int $sellerId, int $buyerId, float $finalPrice, float $commissionAmount = 0.00, float $sellerPayout = 0.00): array
    {
        // Validate positive price
        if ($finalPrice <= 0) {
            throw new \Exception('Final price must be positive');
        }

        // If seller payout not provided, default to final price (backward compatibility)
        if ($sellerPayout === 0.00 && $commissionAmount === 0.00) {
            $sellerPayout = $finalPrice;
        }

        try {
            $sql = "INSERT INTO transactions (item_id, seller_id, buyer_id, final_price, commission_amount, seller_payout, completed_at) 
                    VALUES (:item_id, :seller_id, :buyer_id, :final_price, :commission_amount, :seller_payout, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':item_id' => $itemId,
                ':seller_id' => $sellerId,
                ':buyer_id' => $buyerId,
                ':final_price' => $finalPrice,
                ':commission_amount' => $commissionAmount,
                ':seller_payout' => $sellerPayout
            ]);

            $transactionId = (int)$this->db->lastInsertId();
            
            return $this->findById($transactionId);
        } catch (PDOException $e) {
            throw new \Exception('Failed to create transaction: ' . $e->getMessage());
        }
    }

    /**
     * Find transaction by ID
     * 
     * @param int $transactionId Transaction ID
     * @return array Transaction data with seller, buyer, and item information
     * @throws \Exception If transaction not found
     */
    public function findById(int $transactionId): array
    {
        $sql = "SELECT t.*, 
                       i.title as item_title,
                       i.commission_rate,
                       s.name as seller_name,
                       b.name as buyer_name
                FROM transactions t
                JOIN items i ON t.item_id = i.id
                JOIN users s ON t.seller_id = s.id
                JOIN users b ON t.buyer_id = b.id
                WHERE t.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $transactionId]);
        
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }
        
        return $transaction;
    }

    /**
     * Find all transactions for a specific user (as buyer or seller)
     * 
     * @param int $userId User ID
     * @return array List of transactions ordered by completed_at (descending)
     */
    public function findByUserId(int $userId): array
    {
        $sql = "SELECT t.*, 
                       i.title as item_title,
                       i.commission_rate,
                       s.name as seller_name,
                       b.name as buyer_name
                FROM transactions t
                JOIN items i ON t.item_id = i.id
                JOIN users s ON t.seller_id = s.id
                JOIN users b ON t.buyer_id = b.id
                WHERE t.seller_id = :user_id1 OR t.buyer_id = :user_id2
                ORDER BY t.completed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id1' => $userId, ':user_id2' => $userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Find all transactions where user is the seller
     * 
     * @param int $sellerId Seller user ID
     * @return array List of transactions
     */
    public function findBySellerId(int $sellerId): array
    {
        $sql = "SELECT t.*, 
                       i.title as item_title,
                       b.name as buyer_name
                FROM transactions t
                JOIN items i ON t.item_id = i.id
                JOIN users b ON t.buyer_id = b.id
                WHERE t.seller_id = :seller_id
                ORDER BY t.completed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':seller_id' => $sellerId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Find all transactions where user is the buyer
     * 
     * @param int $buyerId Buyer user ID
     * @return array List of transactions
     */
    public function findByBuyerId(int $buyerId): array
    {
        $sql = "SELECT t.*, 
                       i.title as item_title,
                       s.name as seller_name
                FROM transactions t
                JOIN items i ON t.item_id = i.id
                JOIN users s ON t.seller_id = s.id
                WHERE t.buyer_id = :buyer_id
                ORDER BY t.completed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':buyer_id' => $buyerId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Find transaction by item ID
     * 
     * @param int $itemId Item ID
     * @return array|null Transaction data or null if not found
     */
    public function findByItemId(int $itemId): ?array
    {
        $sql = "SELECT t.*, 
                       i.title as item_title,
                       s.name as seller_name,
                       b.name as buyer_name
                FROM transactions t
                JOIN items i ON t.item_id = i.id
                JOIN users s ON t.seller_id = s.id
                JOIN users b ON t.buyer_id = b.id
                WHERE t.item_id = :item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        
        $transaction = $stmt->fetch();
        
        return $transaction ?: null;
    }
}
