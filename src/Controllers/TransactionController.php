<?php

namespace App\Controllers;

use App\Services\TransactionService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class TransactionController
{
    private TransactionService $transactionService;

    public function __construct()
    {
        $this->transactionService = new TransactionService();
    }

    /**
     * GET /api/transactions
     */
    public function getAll(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $transactions = $this->transactionService->getUserTransactions((int)$user['userId']);
            Response::success(['transactions' => $transactions]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/transactions/:transactionId
     */
    public function getById(int $transactionId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $transaction = $this->transactionService->getTransactionById($transactionId);
            Response::success($transaction);
        } catch (\Exception $e) {
            Response::notFound($e->getMessage());
        }
    }
}
