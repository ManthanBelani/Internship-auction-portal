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

            if (!isset($data['amount'])) {
                Response::badRequest('Amount is required');
                return;
            }

            $amount = (float)$data['amount'];
            if ($amount <= 0) {
                Response::badRequest('Amount must be positive');
                return;
            }

            $method = $data['method'] ?? $data['paymentMethod'] ?? 'bank_transfer';

            $stats = $this->sellerService->getSellerStats((int)$user['userId']);
            
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payouts WHERE seller_id = ? AND status != 'rejected'");
            $stmt->execute([$user['userId']]);
            $pendingTotal = (float)$stmt->fetch()['total'];

            $availableBalance = $stats['totalEarnings'] - $pendingTotal;

            if ($amount > $availableBalance) {
                Response::badRequest("Insufficient balance. Available: \${$availableBalance}");
                return;
            }

            $stmt = $db->prepare("INSERT INTO payouts (seller_id, amount, payment_method) VALUES (?, ?, ?)");
            $stmt->execute([$user['userId'], $amount, $method]);

            Response::success(['message' => 'Payout request submitted successfully'], 201);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller/payouts
     * Get all payouts for the seller
     */
    public function getPayouts(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM payouts WHERE seller_id = ? ORDER BY requested_at DESC");
            $stmt->execute([$user['userId']]);
            $payouts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::success(['payouts' => $payouts]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller/balance
     * Get seller's available balance
     */
    public function getBalance(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $stats = $this->sellerService->getSellerStats((int)$user['userId']);

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payouts WHERE seller_id = ? AND status != 'rejected'");
            $stmt->execute([$user['userId']]);
            $pendingPayouts = (float)$stmt->fetch()['total'];

            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payouts WHERE seller_id = ? AND status = 'pending'");
            $stmt->execute([$user['userId']]);
            $processingPayouts = (float)$stmt->fetch()['total'];

            $available = $stats['totalEarnings'] - $pendingPayouts;

            Response::success([
                'available' => max(0, $available),
                'pending' => $processingPayouts,
                'totalEarnings' => $stats['totalEarnings'],
                'currency' => 'USD'
            ]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/seller/messages/send
     * Send a message (app uses conversationId instead of receiverId)
     */
    public function sendMessageByConversation(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $conversationId = (int)($data['conversationId'] ?? $data['receiverId'] ?? 0);
            $message = $data['message'] ?? '';

            if ($conversationId <= 0 || empty($message)) {
                Response::badRequest('Conversation ID and message content are required');
                return;
            }

            $message = $this->messageModel->create(
                (int)$user['userId'],
                $conversationId,
                null,
                $message
            );

            Response::success($message, 201);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/seller/messages/{messageId}/read
     * Mark a single message as read
     */
    public function markMessageAsRead(int $messageId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
            $stmt->execute([$messageId, $user['userId']]);

            Response::success(['message' => 'Message marked as read']);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
