<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\TransactionService;
use App\Config\Database;
use PDO;

class TransactionServiceTest extends TestCase
{
    private TransactionService $transactionService;
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->transactionService = new TransactionService();
        
        // Create test users if they don't exist
        $this->db->exec("INSERT IGNORE INTO users (id, email, password_hash, name) VALUES 
                        (1, 'seller@test.com', 'password', 'Test Seller'),
                        (2, 'buyer@test.com', 'password', 'Test Buyer')");
        
        // Clean up test data
        $this->db->exec("DELETE FROM transactions WHERE item_id IN (SELECT id FROM items WHERE title LIKE 'Test Transaction%')");
        $this->db->exec("DELETE FROM items WHERE title LIKE 'Test Transaction%'");
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->db->exec("DELETE FROM transactions WHERE item_id IN (SELECT id FROM items WHERE title LIKE 'Test Transaction%')");
        $this->db->exec("DELETE FROM items WHERE title LIKE 'Test Transaction%'");
    }

    public function testCreateTransactionWithDefaultCommission()
    {
        // Create a test item with default commission rate (5%)
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Transaction Item 1', 'Test', 10.00, 100.00, NOW(), 'completed')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Create transaction
        $transaction = $this->transactionService->createTransaction($itemId, 1, 2, 100.00);
        
        $this->assertIsArray($transaction);
        $this->assertEquals($itemId, $transaction['itemId']);
        $this->assertEquals(100.00, $transaction['finalPrice']);
        $this->assertEquals(0.05, $transaction['commissionRate']);
        $this->assertEquals(5.00, $transaction['commissionAmount']);
        $this->assertEquals(95.00, $transaction['sellerPayout']);
    }

    public function testCreateTransactionWithCustomCommission()
    {
        // Create a test item with custom commission rate (10%)
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status, commission_rate) 
                                     VALUES (1, 'Test Transaction Item 2', 'Test', 10.00, 200.00, NOW(), 'completed', 0.10)");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Create transaction
        $transaction = $this->transactionService->createTransaction($itemId, 1, 2, 200.00);
        
        $this->assertIsArray($transaction);
        $this->assertEquals($itemId, $transaction['itemId']);
        $this->assertEquals(200.00, $transaction['finalPrice']);
        $this->assertEquals(0.10, $transaction['commissionRate']);
        $this->assertEquals(20.00, $transaction['commissionAmount']);
        $this->assertEquals(180.00, $transaction['sellerPayout']);
    }

    public function testGetTransactionByIdIncludesCommission()
    {
        // Create a test item
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Transaction Item 3', 'Test', 10.00, 150.00, NOW(), 'completed')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Create transaction
        $createdTransaction = $this->transactionService->createTransaction($itemId, 1, 2, 150.00);
        
        // Retrieve transaction by ID
        $transaction = $this->transactionService->getTransactionById($createdTransaction['transactionId']);
        
        $this->assertIsArray($transaction);
        $this->assertArrayHasKey('commissionRate', $transaction);
        $this->assertArrayHasKey('commissionAmount', $transaction);
        $this->assertArrayHasKey('sellerPayout', $transaction);
        $this->assertEquals(0.05, $transaction['commissionRate']);
        $this->assertEquals(7.50, $transaction['commissionAmount']);
        $this->assertEquals(142.50, $transaction['sellerPayout']);
    }

    public function testGetUserTransactionsIncludesCommission()
    {
        // Create a test item
        $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                                     VALUES (1, 'Test Transaction Item 4', 'Test', 10.00, 120.00, NOW(), 'completed')");
        $stmt->execute();
        $itemId = (int)$this->db->lastInsertId();
        
        // Create transaction
        $this->transactionService->createTransaction($itemId, 1, 2, 120.00);
        
        // Retrieve user transactions
        $transactions = $this->transactionService->getUserTransactions(1);
        
        $this->assertIsArray($transactions);
        $this->assertNotEmpty($transactions);
        
        // Find our test transaction
        $testTransaction = null;
        foreach ($transactions as $transaction) {
            if ($transaction['itemId'] === $itemId) {
                $testTransaction = $transaction;
                break;
            }
        }
        
        $this->assertNotNull($testTransaction, 'Test transaction should be in user transactions');
        $this->assertArrayHasKey('commissionRate', $testTransaction);
        $this->assertArrayHasKey('commissionAmount', $testTransaction);
        $this->assertArrayHasKey('sellerPayout', $testTransaction);
        $this->assertEquals(0.05, $testTransaction['commissionRate']);
        $this->assertEquals(6.00, $testTransaction['commissionAmount']);
        $this->assertEquals(114.00, $testTransaction['sellerPayout']);
    }

    public function testCommissionCalculationAccuracy()
    {
        // Test various price points to ensure accurate commission calculation
        $testCases = [
            ['price' => 50.00, 'rate' => 0.05, 'expectedCommission' => 2.50, 'expectedPayout' => 47.50],
            ['price' => 99.99, 'rate' => 0.05, 'expectedCommission' => 5.00, 'expectedPayout' => 94.99],
            ['price' => 1000.00, 'rate' => 0.03, 'expectedCommission' => 30.00, 'expectedPayout' => 970.00],
        ];
        
        foreach ($testCases as $index => $testCase) {
            // Create a test item
            $stmt = $this->db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status, commission_rate) 
                                         VALUES (1, :title, 'Test', 10.00, :price, NOW(), 'completed', :rate)");
            $stmt->execute([
                ':title' => "Test Transaction Item Accuracy {$index}",
                ':price' => $testCase['price'],
                ':rate' => $testCase['rate']
            ]);
            $itemId = (int)$this->db->lastInsertId();
            
            // Create transaction
            $transaction = $this->transactionService->createTransaction($itemId, 1, 2, $testCase['price']);
            
            $this->assertEquals(
                $testCase['expectedCommission'], 
                $transaction['commissionAmount'],
                "Commission should be {$testCase['expectedCommission']} for price {$testCase['price']} at {$testCase['rate']} rate"
            );
            
            $this->assertEquals(
                $testCase['expectedPayout'], 
                $transaction['sellerPayout'],
                "Seller payout should be {$testCase['expectedPayout']} for price {$testCase['price']} at {$testCase['rate']} rate"
            );
        }
    }
}
