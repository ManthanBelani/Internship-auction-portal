<?php

namespace App\Controllers;

use App\Services\WatchlistService;
use App\Utils\Response;
use App\Config\Database;

class WatchlistController {
    private WatchlistService $watchlistService;

    public function __construct() {
        $db = Database::getConnection();
        $this->watchlistService = new WatchlistService($db);
    }

    /**
     * Add item to watchlist
     * POST /api/watchlist
     */
    public function add(array $data, int $userId): void {
        try {
            // Validate required fields
            if (!isset($data['itemId'])) {
                Response::json(['error' => 'Item ID is required'], 400);
                return;
            }

            $itemId = (int)$data['itemId'];

            // Add to watchlist
            $this->watchlistService->addToWatchlist($userId, $itemId);

            Response::json([
                'message' => 'Item added to watchlist successfully',
                'itemId' => $itemId
            ], 201);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove item from watchlist
     * DELETE /api/watchlist/{itemId}
     */
    public function remove(int $itemId, int $userId): void {
        try {
            $this->watchlistService->removeFromWatchlist($userId, $itemId);

            Response::json([
                'message' => 'Item removed from watchlist successfully',
                'itemId' => $itemId
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get user's watchlist
     * GET /api/watchlist
     */
    public function getWatchlist(int $userId): void {
        try {
            $watchlist = $this->watchlistService->getWatchlist($userId);

            Response::json([
                'userId' => $userId,
                'totalItems' => count($watchlist),
                'items' => $watchlist
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Check if user is watching an item
     * GET /api/watchlist/check/{itemId}
     */
    public function checkWatching(int $itemId, int $userId): void {
        try {
            $isWatching = $this->watchlistService->isWatching($userId, $itemId);

            Response::json([
                'itemId' => $itemId,
                'isWatching' => $isWatching
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
