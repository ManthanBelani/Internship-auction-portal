<?php

namespace App\Controllers;

use App\Services\OrderService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class OrderController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    /**
     * GET /api/orders
     * Get user's orders
     */
    public function getOrders(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $status = $_GET['status'] ?? null;
            $orders = $this->orderService->getUserOrders((int)$user['userId'], $status);
            Response::success(['orders' => $orders]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/orders/:orderId
     * Get order details
     */
    public function getOrderById(int $orderId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $order = $this->orderService->getOrderDetails((int)$user['userId'], $orderId);
            Response::success($order);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * POST /api/orders/create
     * Create order after winning auction
     */
    public function createOrder(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $validator = new \App\Validation\Validator();
            if (!$validator->validate($data, [
                'itemId' => 'required|integer',
                'shippingAddressId' => 'required|integer'
            ])) {
                Response::badRequest($validator->getFirstError());
                return;
            }

            $result = $this->orderService->createOrder(
                (int)$user['userId'],
                (int)$data['itemId'],
                (int)$data['shippingAddressId']
            );

            Response::success($result, 201);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * PUT /api/orders/:orderId/status
     * Update order status
     */
    public function updateOrderStatus(int $orderId, array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['status'])) {
                Response::badRequest('Status is required');
                return;
            }

            $result = $this->orderService->updateOrderStatus(
                (int)$user['userId'],
                $orderId,
                $data['status'],
                $data['trackingNumber'] ?? null
            );

            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * POST /api/orders/:orderId/cancel
     * Cancel order
     */
    public function cancelOrder(int $orderId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $result = $this->orderService->cancelOrder((int)$user['userId'], $orderId);
            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * GET /api/orders/won-items
     * Get items user has won but not yet ordered
     */
    public function getWonItems(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $items = $this->orderService->getWonItems((int)$user['userId']);
            Response::success(['items' => $items]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
