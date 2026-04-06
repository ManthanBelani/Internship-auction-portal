<?php

namespace App\Services;

use App\Config\Database;
use App\Utils\AppLogger;
use App\Utils\Response;

/**
 * Payment Service for BidOrbit
 * 
 * Handles payment processing, payment methods, and payout management.
 * Integrates with Stripe for production use.
 */
class PaymentService
{
    private $db;
    private $stripeSecretKey;
    private $stripePublishableKey;
    private $commissionRate;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        $this->stripePublishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
        $this->commissionRate = (float)($_ENV['COMMISSION_RATE'] ?? 0.05); // 5% default
    }

    /**
     * Create a payment intent for an order
     */
    public function createPaymentIntent(int $userId, float $amount, string $currency = 'usd'): array
    {
        try {
            // Validate amount
            if ($amount <= 0) {
                throw new \Exception('Invalid payment amount');
            }
            
            // In production, call Stripe API
            if ($this->stripeSecretKey && $_ENV['APP_ENV'] === 'production') {
                return $this->createStripePaymentIntent($amount, $currency);
            }
            
            // Development/Testing: Create mock payment intent
            $intentId = 'pi_' . uniqid() . '_' . time();
            $clientSecret = $intentId . '_secret_' . uniqid();
            
            // Store payment intent in database
            $stmt = $this->db->prepare(
                "INSERT INTO payment_intents 
                (intent_id, user_id, amount, currency, status, created_at)
                VALUES (?, ?, ?, ?, 'requires_payment_method', NOW())"
            );
            $stmt->execute([$intentId, $userId, $amount, $currency]);
            
            AppLogger::info('Payment intent created', [
                'intent_id' => $intentId,
                'user_id' => $userId,
                'amount' => $amount,
            ]);
            
            return [
                'intentId' => $intentId,
                'clientSecret' => $clientSecret,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'requires_payment_method',
            ];
        } catch (\Exception $e) {
            AppLogger::error('Failed to create payment intent', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create Stripe payment intent (production)
     */
    private function createStripePaymentIntent(float $amount, string $currency): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->stripeSecretKey . ':');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'amount' => (int)($amount * 100), // Convert to cents
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => 'true'],
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to create payment intent with Stripe');
        }
        
        $data = json_decode($response, true);
        
        return [
            'intentId' => $data['id'],
            'clientSecret' => $data['client_secret'],
            'amount' => $amount,
            'currency' => $currency,
            'status' => $data['status'],
        ];
    }

    /**
     * Confirm a payment
     */
    public function confirmPayment(string $intentId, int $userId): array
    {
        try {
            // Get payment intent from database
            $stmt = $this->db->prepare(
                "SELECT * FROM payment_intents WHERE intent_id = ? AND user_id = ?"
            );
            $stmt->execute([$intentId, $userId]);
            $intent = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$intent) {
                throw new \Exception('Payment intent not found');
            }
            
            // In production, verify with Stripe
            if ($this->stripeSecretKey && $_ENV['APP_ENV'] === 'production') {
                $stripeResult = $this->verifyStripePayment($intentId);
                $status = $stripeResult['status'];
            } else {
                // Development: Simulate successful payment
                $status = 'succeeded';
            }
            
            // Update payment status
            $updateStmt = $this->db->prepare(
                "UPDATE payment_intents SET status = ?, updated_at = NOW() 
                 WHERE intent_id = ?"
            );
            $updateStmt->execute([$status, $intentId]);
            
            if ($status === 'succeeded') {
                // Record transaction
                $this->recordTransaction($userId, $intent['amount'], $intentId);
            }
            
            return [
                'status' => $status,
                'amount' => (float)$intent['amount'],
                'currency' => $intent['currency'],
            ];
        } catch (\Exception $e) {
            AppLogger::error('Payment confirmation failed', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify payment with Stripe (production)
     */
    private function verifyStripePayment(string $intentId): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/payment_intents/$intentId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->stripeSecretKey . ':');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to verify payment with Stripe');
        }
        
        return json_decode($response, true);
    }

    /**
     * Record a transaction
     */
    private function recordTransaction(int $userId, float $amount, string $intentId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO transactions 
            (user_id, amount, type, status, reference_id, created_at)
            VALUES (?, ?, 'payment', 'completed', ?, NOW())"
        );
        $stmt->execute([$userId, $amount, $intentId]);
    }

    /**
     * Get user's payment methods
     */
    public function getPaymentMethods(int $userId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, type, last_four, brand, expiry_month, expiry_year, is_default
                 FROM payment_methods 
                 WHERE user_id = ? AND is_active = 1
                 ORDER BY is_default DESC, created_at DESC"
            );
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            AppLogger::error('Failed to get payment methods', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Add a payment method
     */
    public function addPaymentMethod(int $userId, array $data): array
    {
        try {
            // Validate required fields
            $required = ['type', 'token'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("$field is required");
                }
            }
            
            // In production, verify token with Stripe and get card details
            $type = $data['type'];
            $lastFour = $data['lastFour'] ?? '4242';
            $brand = $data['brand'] ?? 'visa';
            $expiryMonth = $data['expiryMonth'] ?? 12;
            $expiryYear = $data['expiryYear'] ?? date('Y') + 1;
            
            // Check if this is the first payment method (make it default)
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM payment_methods WHERE user_id = ? AND is_active = 1"
            );
            $stmt->execute([$userId]);
            $isDefault = $stmt->fetchColumn() == 0 ? 1 : 0;
            
            // Insert payment method
            $stmt = $this->db->prepare(
                "INSERT INTO payment_methods 
                (user_id, type, last_four, brand, expiry_month, expiry_year, is_default, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId, $type, $lastFour, $brand, $expiryMonth, $expiryYear, $isDefault
            ]);
            
            $methodId = $this->db->lastInsertId();
            
            return [
                'id' => $methodId,
                'type' => $type,
                'lastFour' => $lastFour,
                'brand' => $brand,
                'isDefault' => (bool)$isDefault,
            ];
        } catch (\Exception $e) {
            AppLogger::error('Failed to add payment method', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a payment method
     */
    public function deletePaymentMethod(int $userId, int $methodId): bool
    {
        try {
            // Soft delete
            $stmt = $this->db->prepare(
                "UPDATE payment_methods SET is_active = 0, updated_at = NOW()
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$methodId, $userId]);
            
            if ($stmt->rowCount() > 0) {
                // If this was the default, set another as default
                $stmt = $this->db->prepare(
                    "UPDATE payment_methods SET is_default = 1
                     WHERE user_id = ? AND is_active = 1
                     ORDER BY created_at DESC LIMIT 1"
                );
                $stmt->execute([$userId]);
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            AppLogger::error('Failed to delete payment method', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Calculate commission for a sale
     */
    public function calculateCommission(float $saleAmount): array
    {
        $commission = $saleAmount * $this->commissionRate;
        $minimumCommission = (float)($_ENV['MINIMUM_COMMISSION'] ?? 1.00);
        
        if ($commission < $minimumCommission) {
            $commission = $minimumCommission;
        }
        
        $sellerAmount = $saleAmount - $commission;
        
        return [
            'saleAmount' => $saleAmount,
            'commissionRate' => $this->commissionRate,
            'commission' => round($commission, 2),
            'sellerAmount' => round($sellerAmount, 2),
        ];
    }

    /**
     * Get seller's balance
     */
    public function getSellerBalance(int $userId): array
    {
        try {
            // Get available balance (completed orders older than hold period)
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(seller_amount), 0) as available
                 FROM seller_earnings
                 WHERE seller_id = ? AND status = 'available'"
            );
            $stmt->execute([$userId]);
            $available = (float)$stmt->fetchColumn();
            
            // Get pending balance (on hold)
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(seller_amount), 0) as pending
                 FROM seller_earnings
                 WHERE seller_id = ? AND status = 'pending'"
            );
            $stmt->execute([$userId]);
            $pending = (float)$stmt->fetchColumn();
            
            // Get total earned
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(seller_amount), 0) as total
                 FROM seller_earnings
                 WHERE seller_id = ?"
            );
            $stmt->execute([$userId]);
            $total = (float)$stmt->fetchColumn();
            
            return [
                'available' => round($available, 2),
                'pending' => round($pending, 2),
                'total' => round($total, 2),
                'currency' => 'USD',
            ];
        } catch (\Exception $e) {
            AppLogger::error('Failed to get seller balance', ['error' => $e->getMessage()]);
            return [
                'available' => 0,
                'pending' => 0,
                'total' => 0,
                'currency' => 'USD',
            ];
        }
    }

    /**
     * Request a payout
     */
    public function requestPayout(int $userId, float $amount, string $method): array
    {
        try {
            $balance = $this->getSellerBalance($userId);
            
            if ($amount > $balance['available']) {
                throw new \Exception('Insufficient available balance');
            }
            
            if ($amount < 10) {
                throw new \Exception('Minimum payout amount is $10');
            }
            
            $payoutId = 'po_' . uniqid() . '_' . time();
            
            // Create payout request
            $stmt = $this->db->prepare(
                "INSERT INTO payouts 
                (payout_id, user_id, amount, method, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())"
            );
            $stmt->execute([$payoutId, $userId, $amount, $method]);
            
            // Deduct from available balance
            $stmt = $this->db->prepare(
                "UPDATE seller_earnings SET status = 'processing'
                 WHERE seller_id = ? AND status = 'available'
                 LIMIT " . (int)($amount * 100) // Rough estimate of records to update
            );
            $stmt->execute([$userId]);
            
            AppLogger::info('Payout requested', [
                'payout_id' => $payoutId,
                'user_id' => $userId,
                'amount' => $amount,
                'method' => $method,
            ]);
            
            return [
                'payoutId' => $payoutId,
                'amount' => $amount,
                'method' => $method,
                'status' => 'pending',
                'estimatedProcessingTime' => '3-5 business days',
            ];
        } catch (\Exception $e) {
            AppLogger::error('Failed to request payout', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(int $userId, int $limit = 20, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT t.*, i.title as item_title
                 FROM transactions t
                 LEFT JOIN items i ON t.item_id = i.id
                 WHERE t.user_id = ?
                 ORDER BY t.created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            AppLogger::error('Failed to get payment history', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
