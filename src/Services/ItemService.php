<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Bid;
use App\Models\Transaction;
use App\Config\Database;
use App\Utils\WebSocketClient;
use DateTime;

class ItemService
{
    private Item $itemModel;
    private Bid $bidModel;
    private Transaction $transactionModel;
    private ImageService $imageService;
    private WebSocketClient $wsClient;

    public function __construct()
    {
        $this->itemModel = new Item();
        $this->bidModel = new Bid();
        $this->transactionModel = new Transaction();
        
        // Initialize ImageService for including images in responses
        $db = Database::getConnection();
        $this->imageService = new ImageService($db);
        
        // Initialize WebSocket client
        $this->wsClient = new WebSocketClient();
    }

    /**
     * Create a new auction item
     * 
     * @param int $sellerId Seller user ID
     * @param string $title Item title
     * @param string $description Item description
     * @param float $startingPrice Starting price
     * @param string $endTime Auction end time (ISO 8601 format)
     * @return array Created item data
     * @throws \Exception If validation fails
     */
    public function createItem(int $sellerId, string $title, string $description, float $startingPrice, string $endTime): array
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
        if (empty(trim($title))) {
            throw new \Exception('Title is required');
        }

        if (empty(trim($description))) {
            throw new \Exception('Description is required');
        }

        // Create item
        $item = $this->itemModel->create($sellerId, $title, $description, $startingPrice, $endTime);

        return [
            'itemId' => (int)$item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'startingPrice' => (float)$item['starting_price'],
            'currentPrice' => (float)$item['current_price'],
            'endTime' => $item['end_time'],
            'sellerId' => (int)$item['seller_id'],
            'sellerName' => $item['seller_name'],
            'status' => $item['status'],
            'createdAt' => $item['created_at']
        ];
    }

    /**
     * Get all active auction items
     * 
     * @param array $filters Optional filters (search, sellerId)
     * @return array List of active items
     */
    public function getActiveItems(array $filters = []): array
    {
        $items = $this->itemModel->findActive($filters);

        return array_map(function($item) {
            $itemData = [
                'itemId' => (int)$item['id'],
                'title' => $item['title'],
                'description' => $item['description'],
                'startingPrice' => (float)$item['starting_price'],
                'currentPrice' => (float)$item['current_price'],
                'endTime' => $item['end_time'],
                'sellerId' => (int)$item['seller_id'],
                'sellerName' => $item['seller_name'],
                'status' => $item['status']
            ];
            
            // Include images for each item
            $itemData['images'] = $this->imageService->getItemImages((int)$item['id']);
            
            return $itemData;
        }, $items);
    }

    /**
     * Get item by ID with complete details
     * 
     * @param int $itemId Item ID
     * @return array Item details including current highest bid and images
     * @throws \Exception If item not found
     */
    public function getItemById(int $itemId): array
    {
        $item = $this->itemModel->findById($itemId);

        $result = [
            'itemId' => (int)$item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'startingPrice' => (float)$item['starting_price'],
            'currentPrice' => (float)$item['current_price'],
            'endTime' => $item['end_time'],
            'sellerId' => (int)$item['seller_id'],
            'sellerName' => $item['seller_name'],
            'status' => $item['status'],
            'createdAt' => $item['created_at']
        ];

        // Add highest bidder if exists
        if ($item['highest_bidder_id']) {
            $result['highestBidderId'] = (int)$item['highest_bidder_id'];
        }

        // Get bid count
        $bidCount = $this->bidModel->countByItemId($itemId);
        $result['bidCount'] = $bidCount;

        // Include images for the item
        $result['images'] = $this->imageService->getItemImages($itemId);

        return $result;
    }

    /**
     * Check and complete expired auctions
     * 
     * @return int Number of auctions completed
     */
    public function checkAndCompleteExpiredAuctions(): int
    {
        $expiredItems = $this->itemModel->findExpired();
        $completedCount = 0;

        foreach ($expiredItems as $item) {
            $itemId = (int)$item['id'];
            $result = $this->completeAuction($itemId);
            
            // Count as completed if transaction was created (reserve met)
            if ($result['success'] && $result['transaction']) {
                $completedCount++;
            }
        }

        return $completedCount;
    }

    /**
     * Set reserve price for an item (seller only)
     * 
     * @param int $itemId Item ID
     * @param float $reservePrice Reserve price (must be positive)
     * @return bool Success status
     * @throws \Exception If validation fails
     */
    public function setReservePrice(int $itemId, float $reservePrice): bool
    {
        if ($reservePrice <= 0) {
            throw new \Exception('Reserve price must be positive');
        }

        $this->itemModel->update($itemId, ['reserve_price' => $reservePrice]);
        return true;
    }

    /**
     * Get reserve price for an item (permission-checked)
     * 
     * @param int $itemId Item ID
     * @param int|null $userId User ID (null for anonymous)
     * @return float|null Reserve price (null if not set or user not authorized)
     */
    public function getReservePrice(int $itemId, ?int $userId = null): ?float
    {
        $item = $this->itemModel->findById($itemId);
        
        // Only seller can see the actual reserve price
        if ($userId && (int)$item['seller_id'] === $userId) {
            return $item['reserve_price'] ? (float)$item['reserve_price'] : null;
        }
        
        return null;
    }

    /**
     * Check if reserve price is met by current bid
     * 
     * @param int $itemId Item ID
     * @param float $currentBid Current highest bid
     * @return bool True if reserve is met or no reserve set
     */
    public function isReserveMet(int $itemId, float $currentBid): bool
    {
        $item = $this->itemModel->findById($itemId);
        
        // If no reserve price set, consider it met
        if (!$item['reserve_price']) {
            return true;
        }
        
        return $currentBid >= (float)$item['reserve_price'];
    }

    /**
     * Check reserve status without revealing the amount
     * 
     * @param int $itemId Item ID
     * @return array Status information (reserveSet, reserveMet)
     */
    public function checkReserveStatus(int $itemId): array
    {
        $item = $this->itemModel->findById($itemId);
        
        $reserveSet = !empty($item['reserve_price']);
        $reserveMet = false;
        
        if ($reserveSet) {
            $reserveMet = $this->isReserveMet($itemId, (float)$item['current_price']);
        }
        
        return [
            'reserveSet' => $reserveSet,
            'reserveMet' => $reserveMet
        ];
    }

    /**
     * Complete an auction with reserve price check
     * 
     * @param int $itemId Item ID
     * @return array Completion result (success, message, transaction)
     */
    public function completeAuction(int $itemId): array
    {
        $item = $this->itemModel->findById($itemId);
        $bidCount = $this->bidModel->countByItemId($itemId);
        
        if ($bidCount === 0) {
            // No bids - mark as expired
            $this->itemModel->update($itemId, ['status' => 'expired']);
            
            // Notify WebSocket clients
            $this->wsClient->notifyAuctionEnded($itemId, [
                'finalPrice' => null,
                'winnerId' => null,
                'winnerName' => null,
                'status' => 'expired'
            ]);
            
            return [
                'success' => false,
                'message' => 'No bids placed',
                'transaction' => null
            ];
        }
        
        $highestBid = $this->bidModel->getHighestBid($itemId);
        
        if (!$highestBid) {
            $this->itemModel->update($itemId, ['status' => 'expired']);
            
            // Notify WebSocket clients
            $this->wsClient->notifyAuctionEnded($itemId, [
                'finalPrice' => null,
                'winnerId' => null,
                'winnerName' => null,
                'status' => 'expired'
            ]);
            
            return [
                'success' => false,
                'message' => 'No valid bids',
                'transaction' => null
            ];
        }
        
        $finalPrice = (float)$highestBid['amount'];
        
        // Check reserve price
        if (!$this->isReserveMet($itemId, $finalPrice)) {
            // Reserve not met - mark as completed but don't create transaction
            $this->itemModel->update($itemId, [
                'status' => 'completed',
                'reserve_met' => false
            ]);
            
            // Notify WebSocket clients
            $this->wsClient->notifyAuctionEnded($itemId, [
                'finalPrice' => $finalPrice,
                'winnerId' => null,
                'winnerName' => null,
                'status' => 'reserve_not_met'
            ]);
            
            return [
                'success' => false,
                'message' => 'Reserve price not met',
                'transaction' => null
            ];
        }
        
        // Reserve met or no reserve - create transaction
        $this->itemModel->update($itemId, [
            'status' => 'completed',
            'reserve_met' => true
        ]);
        
        $transaction = $this->transactionModel->create(
            $itemId,
            (int)$item['seller_id'],
            (int)$highestBid['bidder_id'],
            $finalPrice
        );
        
        // Notify WebSocket clients
        $this->wsClient->notifyAuctionEnded($itemId, [
            'finalPrice' => $finalPrice,
            'winnerId' => (int)$highestBid['bidder_id'],
            'winnerName' => $highestBid['bidder_name'] ?? 'Unknown',
            'status' => 'completed'
        ]);
        
        return [
            'success' => true,
            'message' => 'Auction completed successfully',
            'transaction' => $transaction
        ];
    }
}
