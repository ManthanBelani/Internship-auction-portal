<?php

namespace App\Controllers;

use App\Services\ItemService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class ItemController
{
    private ItemService $itemService;

    public function __construct()
    {
        $this->itemService = new ItemService();
    }

    /**
     * POST /api/items
     */
    public function create(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            // Validate required fields
            if (!isset($data['title']) || !isset($data['description']) || 
                !isset($data['startingPrice']) || !isset($data['endTime'])) {
                Response::badRequest('Title, description, startingPrice, and endTime are required');
                return;
            }

            $result = $this->itemService->createItem(
                (int)$user['userId'],
                $data['title'],
                $data['description'],
                (float)$data['startingPrice'],
                $data['endTime']
            );

            Response::success($result, 201);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * GET /api/items
     */
    public function getAll(array $queryParams): void
    {
        try {
            $filters = [];
            
            if (isset($queryParams['search'])) {
                $filters['search'] = $queryParams['search'];
            }
            
            if (isset($queryParams['sellerId'])) {
                $filters['sellerId'] = (int)$queryParams['sellerId'];
            }

            $items = $this->itemService->getActiveItems($filters);
            Response::success(['items' => $items]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/items/:itemId
     */
    public function getById(int $itemId): void
    {
        try {
            $item = $this->itemService->getItemById($itemId);
            
            // Check if user is authenticated and add watchlist status
            $token = \App\Utils\Auth::getTokenFromHeader();
            if ($token) {
                $payload = \App\Utils\Auth::verifyToken($token);
                if ($payload) {
                    // Add watchlist status
                    $watchlistService = new \App\Services\WatchlistService(\App\Config\Database::getConnection());
                    $item['isWatching'] = $watchlistService->isWatching($payload['userId'], $itemId);
                } else {
                    $item['isWatching'] = false;
                }
            } else {
                $item['isWatching'] = false;
            }
            
            Response::success($item);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }
}
