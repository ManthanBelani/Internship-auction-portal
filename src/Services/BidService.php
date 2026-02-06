<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\Item;
use App\Utils\WebSocketClient;
use DateTime;

class BidService
{
    private Bid $bidModel;
    private Item $itemModel;
    private WebSocketClient $wsClient;

    public function __construct()
    {
        $this->bidModel = new Bid();
        $this->itemModel = new Item();
        $this->wsClient = new WebSocketClient();
    }

    /**
     * Place a bid on an auction item
     * 
     * @param int $itemId Item ID
     * @param int $bidderId Bidder user ID
     * @param float $amount Bid amount
     * @return array Created bid data
     * @throws \Exception If validation fails
     */
    public function placeBid(int $itemId, int $bidderId, float $amount): array
    {
        // 1. Validation (Read-only)
        
        // Validate bid amount is positive
        if ($amount <= 0) {
            throw new \Exception('Bid amount must be positive');
        }

        // Get item details
        $item = $this->itemModel->findById($itemId);

        // Validate auction exists and is active
        if ($item['status'] !== 'active') {
            throw new \Exception('Auction is not active');
        }

        // Validate auction end time is in the future
        $endTime = new DateTime($item['end_time']);
        $now = new DateTime();
        
        if ($endTime <= $now) {
            throw new \Exception('Auction has expired');
        }

        // Validate bidder is not the seller
        if ((int)$item['seller_id'] === $bidderId) {
            throw new \Exception('You cannot bid on your own item');
        }

        // Validate bid amount is higher than current price
        if ($amount <= (float)$item['current_price']) {
            throw new \Exception('Bid amount must be higher than current price');
        }

        // Prepare context for notifications
        $previousBidderId = isset($item['highest_bidder_id']) ? (int)$item['highest_bidder_id'] : null;
        $previousBidAmount = (float)$item['current_price'];
        
        // 2. Execution (Transactional)
        $result = \App\Utils\Transaction::run(function() use ($itemId, $bidderId, $amount, $item, $previousBidderId) {
            // Create bid
            $bid = $this->bidModel->create($itemId, $bidderId, $amount);

            // Update item's current price and highest bidder
            $this->itemModel->update($itemId, [
                'current_price' => $amount,
                'highest_bidder_id' => $bidderId
            ]);

            // Check if reserve price is met (if set)
            $reserveMet = null;
            if (isset($item['reserve_price']) && $item['reserve_price'] !== null) {
                $reserveMet = $amount >= (float)$item['reserve_price'];
                if ($reserveMet) {
                    $this->itemModel->update($itemId, ['reserve_met' => true]);
                }
            }

            $bidData = [
                'bidId' => (int)$bid['id'],
                'itemId' => (int)$bid['item_id'],
                'bidderId' => (int)$bid['bidder_id'],
                'bidderName' => $bid['bidder_name'],
                'amount' => (float)$bid['amount'],
                'timestamp' => $bid['timestamp']
            ];

            return [
                'bidData' => $bidData,
                'reserveMet' => $reserveMet
            ];
        });

        $bidData = $result['bidData'];
        $reserveMet = $result['reserveMet'];

        // 3. Notifications (Post-Transaction)
        
        // Send WebSocket notifications
        $this->notifyWebSocket($itemId, $bidData, $reserveMet);
        
        // Send outbid notification to previous bidder
        if ($previousBidderId !== null && $previousBidderId !== $bidderId) {
            $this->wsClient->notifyOutbid($itemId, $previousBidderId, $amount, $previousBidAmount);
        }

        return $bidData;
    }

    /**
     * Notify WebSocket server of bid update
     * 
     * @param int $itemId Item ID
     * @param array $bidData Bid data
     * @param bool|null $reserveMet Whether reserve price is met
     * @return void
     */
    private function notifyWebSocket(int $itemId, array $bidData, ?bool $reserveMet): void
    {
        try {
            $wsData = [
                'bidAmount' => $bidData['amount'],
                'bidderId' => $bidData['bidderId'],
                'bidderName' => $bidData['bidderName'],
                'timestamp' => $bidData['timestamp'],
                'reserveMet' => $reserveMet
            ];
            
            $this->wsClient->notifyBidUpdate($itemId, $wsData);
        } catch (\Exception $e) {
            // Log error but don't fail the bid operation
            error_log("Failed to notify WebSocket server: " . $e->getMessage());
        }
    }

    /**
     * Get bid history for an item
     * 
     * @param int $itemId Item ID
     * @return array List of bids ordered by timestamp (descending)
     * @throws \Exception If item not found
     */
    public function getBidHistory(int $itemId): array
    {
        // Verify item exists
        $this->itemModel->findById($itemId);

        // Get all bids for the item
        $bids = $this->bidModel->findByItemId($itemId);

        return array_map(function($bid) {
            return [
                'bidId' => (int)$bid['id'],
                'bidderId' => (int)$bid['bidder_id'],
                'bidderName' => $bid['bidder_name'],
                'amount' => (float)$bid['amount'],
                'timestamp' => $bid['timestamp']
            ];
        }, $bids);
    }
}
