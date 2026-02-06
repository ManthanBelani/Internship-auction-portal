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
            // Log incoming data for debugging
            error_log("Watchlist add request - userId: $userId, data: " . json_encode($data));
            
            // Check if itemId is provided
            if (!isset($data['itemId']) || empty($data['itemId'])) {
                error_log("Watchlist validation failed: itemId is required");
                Response::json(['error' => 'itemId is required'], 400);
                return;
            }

            // Convert to integer
            $itemId = (int)$data['itemId'];
            
            if ($itemId <= 0) {
                error_log("Watchlist validation failed: invalid itemId");
                Response::json(['error' => 'Invalid itemId'], 400);
                return;
            }

            // Check if already in watchlist
            if ($this->watchlistService->isWatching($userId, $itemId)) {
                // Already in watchlist, return success (idempotent)
                error_log("Item already in watchlist, returning success");
                Response::json([
                    'message' => 'Item already in watchlist',
                    'itemId' => $itemId,
                    'alreadyExists' => true
                ], 200);
                return;
            }

            // Add to watchlist
            $this->watchlistService->addToWatchlist($userId, $itemId);

            Response::json([
                'message' => 'Item added to watchlist successfully',
                'itemId' => $itemId
            ], 201);

        } catch (\Exception $e) {
            error_log("Watchlist add error: " . $e->getMessage());
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
