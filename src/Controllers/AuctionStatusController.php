<?php

namespace App\Controllers;

use App\Services\ItemService;
use App\Services\BidService;
use App\Utils\Response;

/**
 * Controller for real-time auction status and dynamic price updates
 */
class AuctionStatusController
{
    private ItemService $itemService;
    private BidService $bidService;

    public function __construct()
    {
        $this->itemService = new ItemService();
        $this->bidService = new BidService();
    }

    /**
     * GET /api/auction-status/:itemId
     * 
     * Get real-time auction status with current price and latest bids
     * Perfect for polling or real-time updates
     */
    public function getAuctionStatus(int $itemId): void
    {
        try {
            $item = $this->itemService->getItemById($itemId);
            $bidHistory = $this->bidService->getBidHistory($itemId);

            // Get latest 5 bids for quick updates
            $latestBids = array_slice($bidHistory, 0, 5);

            $response = [
                'itemId' => $item['itemId'],
                'title' => $item['title'],
                'status' => $item['status'],
                'currentPrice' => $item['currentPrice'],
                'startingPrice' => $item['startingPrice'],
                'highestBidderId' => $item['highestBidderId'] ?? null,
                'bidCount' => $item['bidCount'],
                'endTime' => $item['endTime'],
                'timeRemaining' => $this->calculateTimeRemaining($item['endTime']),
                'isActive' => $item['status'] === 'active' && strtotime($item['endTime']) > time(),
                'latestBids' => $latestBids,
                'priceIncrease' => $item['currentPrice'] - $item['startingPrice'],
                'priceIncreasePercentage' => $this->calculatePriceIncreasePercentage(
                    $item['startingPrice'],
                    $item['currentPrice']
                ),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            Response::success($response);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * GET /api/auction-status/multiple
     * 
     * Get status for multiple auctions at once
     * Query params: itemIds=1,2,3
     */
    public function getMultipleAuctionStatus(array $queryParams): void
    {
        try {
            if (!isset($queryParams['itemIds'])) {
                Response::badRequest('itemIds parameter is required');
                return;
            }

            $itemIds = explode(',', $queryParams['itemIds']);
            $statuses = [];

            foreach ($itemIds as $itemId) {
                try {
                    $item = $this->itemService->getItemById((int)$itemId);
                    
                    $statuses[] = [
                        'itemId' => $item['itemId'],
                        'currentPrice' => $item['currentPrice'],
                        'bidCount' => $item['bidCount'],
                        'status' => $item['status'],
                        'timeRemaining' => $this->calculateTimeRemaining($item['endTime']),
                        'isActive' => $item['status'] === 'active' && strtotime($item['endTime']) > time()
                    ];
                } catch (\Exception $e) {
                    // Skip items that don't exist
                    continue;
                }
            }

            Response::success([
                'items' => $statuses,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/price-history/:itemId
     * 
     * Get complete price history showing how the price changed over time
     */
    public function getPriceHistory(int $itemId): void
    {
        try {
            $item = $this->itemService->getItemById($itemId);
            $bidHistory = $this->bidService->getBidHistory($itemId);

            $priceHistory = [
                [
                    'timestamp' => $item['createdAt'],
                    'price' => $item['startingPrice'],
                    'type' => 'starting_price',
                    'bidderName' => null
                ]
            ];

            foreach ($bidHistory as $bid) {
                $priceHistory[] = [
                    'timestamp' => $bid['timestamp'],
                    'price' => $bid['amount'],
                    'type' => 'bid',
                    'bidderName' => $bid['bidderName']
                ];
            }

            Response::success([
                'itemId' => $item['itemId'],
                'title' => $item['title'],
                'currentPrice' => $item['currentPrice'],
                'priceHistory' => $priceHistory,
                'totalBids' => count($bidHistory)
            ]);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * Calculate time remaining in human-readable format
     */
    private function calculateTimeRemaining(string $endTime): array
    {
        $now = time();
        $end = strtotime($endTime);
        $diff = $end - $now;

        if ($diff <= 0) {
            return [
                'expired' => true,
                'seconds' => 0,
                'formatted' => 'Expired'
            ];
        }

        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $seconds = $diff % 60;

        $formatted = '';
        if ($days > 0) $formatted .= "{$days}d ";
        if ($hours > 0) $formatted .= "{$hours}h ";
        if ($minutes > 0) $formatted .= "{$minutes}m ";
        if ($days == 0 && $hours == 0) $formatted .= "{$seconds}s";

        return [
            'expired' => false,
            'seconds' => $diff,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'formatted' => trim($formatted)
        ];
    }

    /**
     * Calculate price increase percentage
     */
    private function calculatePriceIncreasePercentage(float $startingPrice, float $currentPrice): float
    {
        if ($startingPrice == 0) return 0;
        return round((($currentPrice - $startingPrice) / $startingPrice) * 100, 2);
    }
}
