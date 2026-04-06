<?php

namespace App\Controllers;

use App\Services\SalesService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class SalesController
{
    private SalesService $salesService;

    public function __construct()
    {
        $this->salesService = new SalesService();
    }

    /**
     * GET /api/seller/sales
     * Get all sales for the seller
     */
    public function getSales(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $status = $_GET['status'] ?? null;
            $sales = $this->salesService->getSellerSales((int)$user['userId'], $status);
            
            Response::success(['sales' => $sales]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller/sales/:id
     * Get sale details
     */
    public function getSaleDetails(int $saleId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $sale = $this->salesService->getSaleDetails((int)$user['userId'], $saleId);
            Response::success($sale);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * PUT /api/seller/sales/:id/ship
     * Mark sale as shipped
     */
    public function markAsShipped(int $saleId, array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['trackingNumber'])) {
                Response::badRequest('Tracking number is required');
                return;
            }

            $result = $this->salesService->markAsShipped(
                (int)$user['userId'],
                $saleId,
                $data['trackingNumber'],
                $data['carrier'] ?? null
            );

            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * PUT /api/seller/sales/:id/deliver
     * Mark sale as delivered
     */
    public function markAsDelivered(int $saleId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $result = $this->salesService->markAsDelivered((int)$user['userId'], $saleId);
            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * GET /api/seller/revenue
     * Get revenue summary
     */
    public function getRevenue(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $period = $_GET['period'] ?? 'month'; // day, week, month, year
            $revenue = $this->salesService->getRevenue((int)$user['userId'], $period);
            
            Response::success($revenue);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
