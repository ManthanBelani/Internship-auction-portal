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

        // Prepare context for notifications
        $previousBidderId = null;
        $previousBidAmount = 0;
        $item = null;
        
        // 2. Execution (Transactional)
        $result = \App\Utils\Transaction::run(function() use ($itemId, $bidderId, $amount, &$item, &$previousBidderId, &$previousBidAmount) {
            // Get item details inside transaction with lock
            $item = $this->itemModel->findByIdForUpdate($itemId);

            // Re-validate everything with the locked data
            if ($item['status'] !== 'active') {
                throw new \Exception('Auction is not active');
            }

            $endTime = new DateTime($item['end_time']);
            if ($endTime <= new DateTime()) {
                throw new \Exception('Auction has expired');
            }

            if ((int)$item['seller_id'] === $bidderId) {
                throw new \Exception('You cannot bid on your own item');
            }

            if ($amount <= (float)$item['current_price']) {
                throw new \Exception('Bid amount must be higher than current price (' . $item['current_price'] . ')');
            }

            $previousBidderId = isset($item['highest_bidder_id']) ? (int)$item['highest_bidder_id'] : null;
            $previousBidAmount = (float)$item['current_price'];

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

            // Soft-Close Logic (Anti-Sniping) inside transaction
            $softCloseThreshold = 120; // 2 minutes
            $extensionTime = 120; // 2 minutes
            $secondsRemaining = $endTime->getTimestamp() - (new DateTime())->getTimestamp();
            $newEndTime = null;

            if ($secondsRemaining <= $softCloseThreshold) {
                $newEndTime = (clone $endTime)->modify("+{$extensionTime} seconds")->format('Y-m-d H:i:s');
                $this->itemModel->update($itemId, ['end_time' => $newEndTime]);
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
                'reserveMet' => $reserveMet,
                'newEndTime' => $newEndTime
            ];
        });

        $bidData = $result['bidData'];
        $reserveMet = $result['reserveMet'];
        $newEndTime = $result['newEndTime'];

        if ($newEndTime) {
            // Notify WebSocket of extension
            $this->wsClient->notifyAuctionExtended($itemId, $newEndTime);
        }

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
