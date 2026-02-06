<?php

namespace App\Controllers;

use App\Models\Bid;
use App\Models\Item;
use App\Services\NotificationQueueService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Config\Database;

class BuyerController
{
    private Bid $bidModel;
    private Item $itemModel;
    private NotificationQueueService $notificationService;

    public function __construct()
    {
        $this->bidModel = new Bid();
        $this->itemModel = new Item();
        $this->notificationService = new NotificationQueueService(Database::getConnection());
    }

    /**
     * GET /api/my/bids
     * Get items the current user has bid on
     */
    public function getMyBids(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $items = $this->bidModel->findItemsByBidderId((int)$user['userId']);
            
            $formattedItems = array_map(function($item) {
                return [
                    'itemId' => (int)$item['id'],
                    'title' => $item['title'],
                    'currentPrice' => (float)$item['current_price'],
                    'myHighestBid' => (float)$item['my_highest_bid'],
                    'status' => $item['status'],
                    'endTime' => $item['end_time'],
                    'isWinning' => ((int)$item['highest_bidder_id'] === (int)$_SESSION['user_id']), // This assumes session or we pass user from auth
                    'sellerName' => $item['seller_name']
                ];
            }, $items);

            // Fix isWinning logic since we use $user array
            foreach ($formattedItems as &$fItem) {
                $fItem['isWinning'] = ((int)$items[array_search($fItem['itemId'], array_column($items, 'id'))]['highest_bidder_id'] === (int)$user['userId']);
            }

            Response::success(['items' => $formattedItems]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/notifications
     * Get user notifications
     */
    public function getNotifications(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $notifications = $this->notificationService->getPendingNotifications((int)$user['userId']);
            Response::success(['notifications' => $notifications]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/notifications/{id}/read
     * Mark notification as read (delivered)
     */
    public function markAsRead(int $notificationId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $this->notificationService->markAsDelivered($notificationId);
            Response::success(['message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
