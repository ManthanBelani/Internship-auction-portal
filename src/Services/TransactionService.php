<?php

namespace App\Services;

use App\Models\Transaction;
use App\Config\Database;
use PDO;

class TransactionService
{
    private Transaction $transactionModel;
    private CommissionService $commissionService;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $db = Database::getConnection();
        $this->commissionService = new CommissionService($db);
    }

    /**
     * Create a new transaction
     * 
     * @param int $itemId Item ID
     * @param int $sellerId Seller user ID
     * @param int $buyerId Buyer user ID
     * @param float $finalPrice Final transaction price
     * @return array Created transaction data
     * @throws \Exception If validation fails
     */
    public function createTransaction(int $itemId, int $sellerId, int $buyerId, float $finalPrice): array
    {
        // Get commission rate for the item
        $commissionRate = $this->commissionService->getCommissionRate($itemId);
        
        // Calculate commission and seller payout
        $commissionAmount = $this->commissionService->calculateCommission($finalPrice, $commissionRate);
        $sellerPayout = $this->commissionService->calculateSellerPayout($finalPrice, $commissionAmount);
        
        // Create transaction with commission data
        $transaction = $this->transactionModel->create(
            $itemId, 
            $sellerId, 
            $buyerId, 
            $finalPrice,
            $commissionAmount,
            $sellerPayout
        );

        return [
            'transactionId' => (int)$transaction['id'],
            'itemId' => (int)$transaction['item_id'],
            'itemTitle' => $transaction['item_title'],
            'sellerId' => (int)$transaction['seller_id'],
            'sellerName' => $transaction['seller_name'],
            'buyerId' => (int)$transaction['buyer_id'],
            'buyerName' => $transaction['buyer_name'],
            'finalPrice' => (float)$transaction['final_price'],
            'commissionRate' => $commissionRate,
            'commissionAmount' => (float)$transaction['commission_amount'],
            'sellerPayout' => (float)$transaction['seller_payout'],
            'completedAt' => $transaction['completed_at']
        ];
    }

    /**
     * Get all transactions for a user (as buyer or seller)
     * 
     * @param int $userId User ID
     * @return array List of transactions
     */
    public function getUserTransactions(int $userId): array
    {
        $transactions = $this->transactionModel->findByUserId($userId);

        return array_map(function($transaction) {
            return [
                'transactionId' => (int)$transaction['id'],
                'itemId' => (int)$transaction['item_id'],
                'itemTitle' => $transaction['item_title'],
                'sellerId' => (int)$transaction['seller_id'],
                'sellerName' => $transaction['seller_name'],
                'buyerId' => (int)$transaction['buyer_id'],
                'buyerName' => $transaction['buyer_name'],
                'finalPrice' => (float)$transaction['final_price'],
                'commissionRate' => (float)($transaction['commission_rate'] ?? 0.05),
                'commissionAmount' => (float)($transaction['commission_amount'] ?? 0),
                'sellerPayout' => (float)($transaction['seller_payout'] ?? $transaction['final_price']),
                'completedAt' => $transaction['completed_at']
            ];
        }, $transactions);
    }

    /**
     * Get transaction by ID
     * 
     * @param int $transactionId Transaction ID
     * @return array Transaction details
     * @throws \Exception If transaction not found
     */
    public function getTransactionById(int $transactionId): array
    {
        $transaction = $this->transactionModel->findById($transactionId);

        return [
            'transactionId' => (int)$transaction['id'],
            'itemId' => (int)$transaction['item_id'],
            'itemTitle' => $transaction['item_title'],
            'sellerId' => (int)$transaction['seller_id'],
            'sellerName' => $transaction['seller_name'],
            'buyerId' => (int)$transaction['buyer_id'],
            'buyerName' => $transaction['buyer_name'],
            'finalPrice' => (float)$transaction['final_price'],
            'commissionRate' => (float)($transaction['commission_rate'] ?? 0.05),
            'commissionAmount' => (float)($transaction['commission_amount'] ?? 0),
            'sellerPayout' => (float)($transaction['seller_payout'] ?? $transaction['final_price']),
            'completedAt' => $transaction['completed_at']
        ];
    }
}
