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

    /**
     * POST /api/transactions/{id}/pay
     * Mock payment endpoint
     */
    public function pay(int $transactionId, array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $transaction = $this->transactionService->getTransactionById($transactionId);

            // Verify the user is the buyer
            if ((int)$transaction['buyer_id'] !== (int)$user['userId']) {
                Response::forbidden('Only the buyer can pay for this transaction');
                return;
            }

            if ($transaction['payment_status'] === 'paid') {
                Response::badRequest('Successfully already paid for this transaction');
                return;
            }

            // Mock payment processing (always success for now)
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE transactions SET payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$transactionId]);

            Response::success(['message' => 'Payment successful', 'transactionId' => $transactionId]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
