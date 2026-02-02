<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CommissionService;
use App\Config\Database;
use PDO;

class CommissionServiceTest extends TestCase
{
    private CommissionService $commissionService;
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->commissionService = new CommissionService($this->db);
        
        // Clean up test data
        $this->db->exec("DELETE FROM transactions WHERE item_id IN (SELECT id FROM items WHERE title LIKE 'Test Commission%')");
        $this->db->exec("DELETE FROM items WHERE title LIKE 'Test Commission%'");
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->db->exec("DELETE FROM transactions WHERE item_id IN (SELECT id FROM items WHERE title LIKE 'Test Commission%')");
        $this->db->exec("DELETE FROM items WHERE title LIKE 'Test Commission%'");
    }

    public function testCalculateCommissionWithDefaultRate()
    {
        $salePrice = 100.00;
        $commission = $this->commissionService->calculateCommission($salePrice);
        
        $this->assertEquals(5.00, $commission, 'Default 5% commission should be $5.00 on $100.00');
    }

    public function testCalculateCommissionWithCustomRate()
    {
        $salePrice = 100.00;
        $customRate = 0.10; // 10%
        $commission = $this->commissionService->calculateCommission($salePrice, $customRate);
        
        $this->assertEquals(10.00, $commission, '10% commission should be $10.00 on $100.00');
    }

    public function testCalculateCommissionRounding()
    {
        $salePrice = 99.99;
        $commission = $this->commissionService->calculateCommission($salePrice);
        
        $this->assertEquals(5.00, $commission, 'Commission should be rounded to 2 decimal places');
    }

    public function testCalculateSellerPayout()
    {
        $salePrice = 100.00;
        $commission = 5.00;
        $payout = $this->commissionService->calculateSellerPayout($salePrice, $commission);
        
        $this->assertEquals(95.00, $payout, 'Seller payout should be sale price minus commission');
    }

    public function testGetCommissionRateForItemWithDefaultRate()
    {
        // Create a test item with default commission rate
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 1', 'Test', 10.00, 10.00, DATE_ADD(NOW(), INTERVAL 1 DAY), 'active')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        $rate = $this->commissionService->getCommissionRate($itemId);
        
        $this->assertEquals(0.05, $rate, 'Default commission rate should be 5%');
    }

    public function testSetAndGetCustomCommissionRate()
    {
        // Create a test item
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 2', 'Test', 10.00, 10.00, DATE_ADD(NOW(), INTERVAL 1 DAY), 'active')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Set custom rate
        $customRate = 0.08; // 8%
        $result = $this->commissionService->setCommissionRate($itemId, $customRate);
        
        $this->assertTrue($result, 'Setting commission rate should succeed');
        
        // Verify the rate was set
        $rate = $this->commissionService->getCommissionRate($itemId);
        $this->assertEquals(0.08, $rate, 'Custom commission rate should be 8%');
    }

    public function testSetCommissionRateValidation()
    {
        // Create a test item
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 3', 'Test', 10.00, 10.00, DATE_ADD(NOW(), INTERVAL 1 DAY), 'active')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Test invalid rate (negative)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Commission rate must be between 0 and 1');
        $this->commissionService->setCommissionRate($itemId, -0.05);
    }

    public function testSetCommissionRateValidationTooHigh()
    {
        // Create a test item
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 4', 'Test', 10.00, 10.00, DATE_ADD(NOW(), INTERVAL 1 DAY), 'active')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Test invalid rate (over 100%)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Commission rate must be between 0 and 1');
        $this->commissionService->setCommissionRate($itemId, 1.5);
    }

    public function testGetTotalPlatformEarnings()
    {
        // Create test items and transactions
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 5', 'Test', 10.00, 100.00, NOW(), 'completed')");
        $stmt->execute();
        $itemId1 = (int)$this->db->lastInsertId();
        
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 6', 'Test', 10.00, 200.00, NOW(), 'completed')");
        $stmt->execute();
        $itemId2 = (int)$this->db->lastInsertId();
        
        // Create transactions with commission
        $stmt = $this->db->prepare("INSERT INTO transactions (item_id, seller_id, buyer_id, final_price, commission_amount, seller_payout, completed_at) 
                                     VALUES (:item_id, 1, 2, :final_price, :commission, :payout, NOW())");
        
        $stmt->execute([
            ':item_id' => $itemId1,
            ':final_price' => 100.00,
            ':commission' => 5.00,
            ':payout' => 95.00
        ]);
        
        $stmt->execute([
            ':item_id' => $itemId2,
            ':final_price' => 200.00,
            ':commission' => 10.00,
            ':payout' => 190.00
        ]);
        
        $totalEarnings = $this->commissionService->getTotalPlatformEarnings();
        
        // Should be at least 15.00 from our test transactions
        $this->assertGreaterThanOrEqual(15.00, $totalEarnings, 'Total platform earnings should include test transactions');
    }

    public function testGetEarningsByDateRange()
    {
        // Create test item and transaction
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Commission Item 7', 'Test', 10.00, 150.00, NOW(), 'completed')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Create transaction with commission
        $stmt = $this->db->prepare("INSERT INTO transactions (item_id, seller_id, buyer_id, final_price, commission_amount, seller_payout, completed_at) 
                                     VALUES (:item_id, 1, 2, 150.00, 7.50, 142.50, NOW())");
        $stmt->execute([':item_id' => $itemId]);
        
        // Get earnings for today
        $today = date('Y-m-d');
        $earnings = $this->commissionService->getEarningsByDateRange($today, $today);
        
        // Should be at least 7.50 from our test transaction
        $this->assertGreaterThanOrEqual(7.50, $earnings, 'Earnings for today should include test transaction');
    }
}
