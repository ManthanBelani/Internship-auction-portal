<?php

namespace App\Controllers;

use App\Services\BidService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class BidController
{
    private BidService $bidService;

    public function __construct()
    {
        $this->bidService = new BidService();
    }

    /**
     * POST /api/bids
     */
    public function create(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            // Validate required fields
            if (!isset($data['itemId']) || !isset($data['amount'])) {
                Response::badRequest('ItemId and amount are required');
                return;
            }

            $result = $this->bidService->placeBid(
                (int)$data['itemId'],
                (int)$user['userId'],
                (float)$data['amount']
            );

            Response::success($result, 201);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'cannot bid on your own')) {
                Response::forbidden($e->getMessage());
            } else {
                Response::badRequest($e->getMessage());
            }
        }
    }

    /**
     * GET /api/bids/:itemId
     */
    public function getHistory(int $itemId): void
    {
        try {
            $bids = $this->bidService->getBidHistory($itemId);
            Response::success(['bids' => $bids]);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }
}
