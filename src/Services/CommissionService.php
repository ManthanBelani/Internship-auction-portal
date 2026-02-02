<?php

namespace App\Services;

use PDO;

class CommissionService {
    private PDO $db;
    private float $defaultCommissionRate = 0.05; // 5%

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Calculate commission amount based on sale price and commission rate
     * 
     * @param float $salePrice The final sale price
     * @param float|null $commissionRate Optional custom commission rate (defaults to 5%)
     * @return float The calculated commission amount
     */
    public function calculateCommission(float $salePrice, ?float $commissionRate = null): float {
        $rate = $commissionRate ?? $this->defaultCommissionRate;
        return round($salePrice * $rate, 2);
    }

    /**
     * Get the commission rate for a specific item
     * 
     * @param int $itemId The item ID
     * @return float The commission rate (custom or default)
     */
    public function getCommissionRate(int $itemId): float {
        $sql = "SELECT commission_rate FROM items WHERE id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['commission_rate'] !== null) {
            return (float)$result['commission_rate'];
        }
        
        return $this->defaultCommissionRate;
    }

    /**
     * Set a custom commission rate for a specific item
     * 
     * @param int $itemId The item ID
     * @param float $rate The commission rate (e.g., 0.05 for 5%)
     * @return bool True if successful
     * @throws \Exception If rate is invalid
     */
    public function setCommissionRate(int $itemId, float $rate): bool {
        if ($rate < 0 || $rate > 1) {
            throw new \Exception('Commission rate must be between 0 and 1');
        }

        $sql = "UPDATE items SET commission_rate = :rate WHERE id = :item_id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':rate' => $rate,
            ':item_id' => $itemId
        ]);
    }

    /**
     * Calculate the seller's payout after commission is deducted
     * 
     * @param float $salePrice The final sale price
     * @param float $commission The commission amount
     * @return float The seller's payout amount
     */
    public function calculateSellerPayout(float $salePrice, float $commission): float {
        return round($salePrice - $commission, 2);
    }

    /**
     * Get total platform earnings from all completed transactions
     * 
     * @return float Total commission amount earned
     */
    public function getTotalPlatformEarnings(): float {
        $sql = "SELECT COALESCE(SUM(commission_amount), 0) as total_earnings FROM transactions";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (float)$result['total_earnings'];
    }

    /**
     * Get platform earnings within a specific date range
     * 
     * @param string $startDate Start date (YYYY-MM-DD format)
     * @param string $endDate End date (YYYY-MM-DD format)
     * @return float Total commission amount earned in date range
     */
    public function getEarningsByDateRange(string $startDate, string $endDate): float {
        $sql = "SELECT COALESCE(SUM(commission_amount), 0) as total_earnings 
                FROM transactions 
                WHERE DATE(completed_at) BETWEEN :start_date AND :end_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (float)$result['total_earnings'];
    }
}
