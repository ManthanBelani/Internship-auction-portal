<?php

namespace App\Controllers;

use App\Services\SellerService;
use App\Services\ItemService;
use App\Models\Message;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Config\Database;

class SellerController
{
    private SellerService $sellerService;
    private ItemService $itemService;
    private Message $messageModel;

    public function __construct()
    {
        $this->sellerService = new SellerService();
        $this->itemService = new ItemService();
        $this->messageModel = new Message();
    }

    /**
     * GET /api/v1/seller/stats
     * Get seller dashboard statistics
     */
    public function getStats(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $stats = $this->sellerService->getSellerStats((int)$user['userId']);
            Response::success($stats);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/seller/listings
     * Get all listings for the current seller
     */
    public function getListings(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $filters = ['sellerId' => (int)$user['userId']];
            $items = $this->itemService->getActiveItems($filters);
            
            Response::success(['listings' => $items]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/v1/items/{id}
     * Update an active listing (Inventory Management)
     */
    public function updateListing(int $itemId, array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            // Verify ownership
            $item = $this->itemService->getItemById($itemId);
            if ($item['sellerId'] !== (int)$user['userId']) {
                Response::forbidden('You do not have permission to edit this listing');
                return;
            }

            // Restrictions: Cannot change price or end time if bids exist
            if ($item['bidCount'] > 0 && (isset($data['startingPrice']) || isset($data['endTime']))) {
                Response::badRequest('Cannot change price or end time once bids have been placed');
                return;
            }

            // Update logic (simplified for now, ideally in ItemService)
            $db = Database::getConnection();
            $fields = [];
            $params = [':id' => $itemId];

            if (isset($data['title'])) {
                $fields[] = "title = :title";
                $params[':title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $fields[] = "description = :description";
                $params[':description'] = $data['description'];
            }

            if (empty($fields)) {
                Response::badRequest('No fields to update');
                return;
            }

            $sql = "UPDATE items SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            Response::success(['message' => 'Listing updated successfully']);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/messages
     * Get recent conversations for the seller
     */
    public function getMessages(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $conversations = $this->messageModel->getRecentConversations((int)$user['userId']);
            Response::success(['conversations' => $conversations]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/messages/{userId}
     * Get full conversation with a specific user
     */
    public function getConversation(int $otherUserId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $itemId = isset($_GET['itemId']) ? (int)$_GET['itemId'] : null;
            $messages = $this->messageModel->getConversation((int)$user['userId'], $otherUserId, $itemId);
            
            // Mark as read
            $this->messageModel->markAsRead((int)$user['userId'], $otherUserId, $itemId);

            Response::success(['messages' => $messages]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/messages
     * Send a message to a buyer/seller
     */
    public function sendMessage(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['receiverId']) || !isset($data['message'])) {
                Response::badRequest('Receiver ID and message content are required');
                return;
            }

            $itemId = isset($data['itemId']) ? (int)$data['itemId'] : null;
            $message = $this->messageModel->create(
                (int)$user['userId'],
                (int)$data['receiverId'],
                $itemId,
                $data['message']
            );

            Response::success($message, 201);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/shipping/track
     * Update shipping information for a sold item
     */
    public function updateShipping(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['transactionId']) || !isset($data['trackingNumber'])) {
                Response::badRequest('Transaction ID and tracking number are required');
                return;
            }

            // Verify ownership via transaction
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT seller_id FROM transactions WHERE id = ?");
            $stmt->execute([$data['transactionId']]);
            $transaction = $stmt->fetch();

            if (!$transaction || $transaction['seller_id'] !== (int)$user['userId']) {
                Response::forbidden('Access denied');
                return;
            }

            $stmt = $db->prepare("UPDATE transactions SET tracking_number = ?, shipping_status = 'shipped' WHERE id = ?");
            $stmt->execute([$data['trackingNumber'], $data['transactionId']]);

            Response::success(['message' => 'Shipping information updated']);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/payouts
     * Request a payout of earnings
     */
    public function requestPayout(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['amount']) || !isset($data['method'])) {
                Response::badRequest('Amount and payment method are required');
                return;
            }

            $amount = (float)$data['amount'];
            if ($amount <= 0) {
                Response::badRequest('Amount must be positive');
                return;
            }

            // Check if seller has enough balance
            $stats = $this->sellerService->getSellerStats((int)$user['userId']);
            
            // Get already withdrawn or pending payouts
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT SUM(amount) as total FROM payouts WHERE seller_id = ? AND status != 'rejected'");
            $stmt->execute([$user['userId']]);
            $pendingTotal = (float)$stmt->fetch()['total'];

            $availableBalance = $stats['totalEarnings'] - $pendingTotal;

            if ($amount > $availableBalance) {
                Response::badRequest("Insufficient balance. Available: {$availableBalance}");
                return;
            }

            // Record payout request
            $stmt = $db->prepare("INSERT INTO payouts (seller_id, amount, payment_method) VALUES (?, ?, ?)");
            $stmt->execute([$user['userId'], $amount, $data['method']]);

            Response::success(['message' => 'Payout request submitted successfully'], 201);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
