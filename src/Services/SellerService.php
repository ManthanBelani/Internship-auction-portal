<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Transaction;
use App\Config\Database;
use PDO;

class SellerService
{
    private PDO $db;
    private Item $itemModel;
    private Transaction $transactionModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->itemModel = new Item();
        $this->transactionModel = new Transaction();
    }

    /**
     * Get statistics for the seller dashboard
     * 
     * @param int $sellerId The seller's user ID
     * @return array Dashboard statistics
     */
    public function getSellerStats(int $sellerId): array
    {
        // 1. Active Listings
        $sqlActive = "SELECT COUNT(*) as count FROM items WHERE seller_id = :seller_id AND status = 'active'";
        $stmtActive = $this->db->prepare($sqlActive);
        $stmtActive->execute([':seller_id' => $sellerId]);
        $activeCount = (int)$stmtActive->fetch(PDO::FETCH_ASSOC)['count'];

        // 2. Sold Items (Auctions ended and reserve met)
        $sqlSold = "SELECT COUNT(*) as count FROM items WHERE seller_id = :seller_id AND status = 'completed' AND reserve_met = 1";
        $stmtSold = $this->db->prepare($sqlSold);
        $stmtSold->execute([':seller_id' => $sellerId]);
        $soldCount = (int)$stmtSold->fetch(PDO::FETCH_ASSOC)['count'];

        // 3. Total Earnings (Net payout to seller)
        $sqlEarnings = "SELECT COALESCE(SUM(seller_payout), 0) as total FROM transactions WHERE seller_id = :seller_id";
        $stmtEarnings = $this->db->prepare($sqlEarnings);
        $stmtEarnings->execute([':seller_id' => $sellerId]);
        $totalEarnings = (float)$stmtEarnings->fetch(PDO::FETCH_ASSOC)['total'];

        // 4. Unread Messages count
        $sqlMessages = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = :seller_id AND is_read = 0";
        $stmtMessages = $this->db->prepare($sqlMessages);
        $stmtMessages->execute([':seller_id' => $sellerId]);
        $unreadMessages = (int)$stmtMessages->fetch(PDO::FETCH_ASSOC)['count'];

        // 5. Total Bids received across all active items
        $sqlTotalBids = "SELECT COUNT(*) as count FROM bids b 
                         JOIN items i ON b.item_id = i.id 
                         WHERE i.seller_id = :seller_id AND i.status = 'active'";
        $stmtBids = $this->db->prepare($sqlTotalBids);
        $stmtBids->execute([':seller_id' => $sellerId]);
        $totalBids = (int)$stmtBids->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'activeListings' => $activeCount,
            'soldItems' => $soldCount,
            'totalEarnings' => $totalEarnings,
            'unreadMessages' => $unreadMessages,
            'totalBidsReceived' => $totalBids,
            'currency' => 'USD' // Default currency
        ];
    }
}
