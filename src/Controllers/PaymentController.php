<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class PaymentController
{
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    /**
     * POST /api/payments/create-intent
     * Create a payment intent for Stripe
     */
    public function createPaymentIntent(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['itemId']) || !isset($data['amount'])) {
                Response::badRequest('Item ID and amount are required');
                return;
            }

            $result = $this->paymentService->createPaymentIntent(
                (int)$user['userId'],
                (int)$data['itemId'],
                (float)$data['amount']
            );

            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * POST /api/payments/confirm
     * Confirm payment and create transaction
     */
    public function confirmPayment(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            if (!isset($data['paymentIntentId']) || !isset($data['itemId'])) {
                Response::badRequest('Payment intent ID and item ID are required');
                return;
            }

            $result = $this->paymentService->confirmPayment(
                (int)$user['userId'],
                (int)$data['itemId'],
                $data['paymentIntentId'],
                $data['paymentMethod'] ?? 'stripe'
            );

            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * GET /api/payments/methods
     * Get user's saved payment methods
     */
    public function getPaymentMethods(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $methods = $this->paymentService->getPaymentMethods((int)$user['userId']);
            Response::success(['methods' => $methods]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/payments/methods
     * Add a new payment method
     */
    public function addPaymentMethod(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $result = $this->paymentService->addPaymentMethod(
                (int)$user['userId'],
                $data
            );

            Response::success($result, 201);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * DELETE /api/payments/methods/:methodId
     * Remove a payment method
     */
    public function deletePaymentMethod(int $methodId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $this->paymentService->deletePaymentMethod((int)$user['userId'], $methodId);
            Response::success(['message' => 'Payment method deleted']);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * GET /api/payments/history
     * Get payment history
     */
    public function getPaymentHistory(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $history = $this->paymentService->getPaymentHistory((int)$user['userId']);
            Response::success(['payments' => $history]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
