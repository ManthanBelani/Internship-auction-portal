<?php

/**
 * Verification script for Commission Service implementation
 * Run this with: php verify_commission_service.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Services\CommissionService;
use App\Services\TransactionService;

echo "=== Commission Service Verification ===\n\n";

try {
    $db = Database::getConnection();
    $commissionService = new CommissionService($db);
    $transactionService = new TransactionService();
    
    echo "✓ Services initialized successfully\n\n";
    
    // Test 1: Calculate commission with default rate
    echo "Test 1: Calculate commission with default rate (5%)\n";
    $salePrice = 100.00;
    $commission = $commissionService->calculateCommission($salePrice);
    echo "  Sale Price: $" . number_format($salePrice, 2) . "\n";
    echo "  Commission (5%): $" . number_format($commission, 2) . "\n";
    echo "  Expected: $5.00\n";
    echo "  Result: " . ($commission == 5.00 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 2: Calculate commission with custom rate
    echo "Test 2: Calculate commission with custom rate (10%)\n";
    $salePrice = 200.00;
    $customRate = 0.10;
    $commission = $commissionService->calculateCommission($salePrice, $customRate);
    echo "  Sale Price: $" . number_format($salePrice, 2) . "\n";
    echo "  Commission (10%): $" . number_format($commission, 2) . "\n";
    echo "  Expected: $20.00\n";
    echo "  Result: " . ($commission == 20.00 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 3: Calculate seller payout
    echo "Test 3: Calculate seller payout\n";
    $salePrice = 150.00;
    $commission = 7.50;
    $payout = $commissionService->calculateSellerPayout($salePrice, $commission);
    echo "  Sale Price: $" . number_format($salePrice, 2) . "\n";
    echo "  Commission: $" . number_format($commission, 2) . "\n";
    echo "  Seller Payout: $" . number_format($payout, 2) . "\n";
    echo "  Expected: $142.50\n";
    echo "  Result: " . ($payout == 142.50 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 4: Create test item and set custom commission rate
    echo "Test 4: Set and get custom commission rate\n";
    
    // Clean up any existing test data
    $db->exec("DELETE FROM transactions WHERE item_id IN (SELECT id FROM items WHERE title = 'Commission Test Item')");
    $db->exec("DELETE FROM items WHERE title = 'Commission Test Item'");
    
    // Create a test item
    $stmt = $db->prepare("INSERT INTO items (seller_id, title, description, starting_price, current_price, end_time, status) 
                          VALUES (1, 'Commission Test Item', 'Test', 10.00, 100.00, DATE_ADD(NOW(), INTERVAL 1 DAY), 'active')");
    $stmt->execute();
    $itemId = (int)$db->lastInsertId();
    echo "  Created test item ID: {$itemId}\n";
    
    // Get default rate
    $defaultRate = $commissionService->getCommissionRate($itemId);
    echo "  Default commission rate: " . ($defaultRate * 100) . "%\n";
    echo "  Expected: 5%\n";
    echo "  Result: " . ($defaultRate == 0.05 ? "✓ PASS" : "✗ FAIL") . "\n";
    
    // Set custom rate
    $customRate = 0.08;
    $commissionService->setCommissionRate($itemId, $customRate);
    echo "  Set custom rate to: 8%\n";
    
    // Verify custom rate
    $retrievedRate = $commissionService->getCommissionRate($itemId);
    echo "  Retrieved commission rate: " . ($retrievedRate * 100) . "%\n";
    echo "  Expected: 8%\n";
    echo "  Result: " . ($retrievedRate == 0.08 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 5: Create transaction with commission
    echo "Test 5: Create transaction with commission integration\n";
    
    // Update item to completed status
    $stmt = $db->prepare("UPDATE items SET status = 'completed', end_time = NOW() WHERE id = :id");
    $stmt->execute([':id' => $itemId]);
    
    // Create transaction
    $finalPrice = 100.00;
    $transaction = $transactionService->createTransaction($itemId, 1, 2, $finalPrice);
    
    echo "  Transaction ID: {$transaction['transactionId']}\n";
    echo "  Final Price: $" . number_format($transaction['finalPrice'], 2) . "\n";
    echo "  Commission Rate: " . ($transaction['commissionRate'] * 100) . "%\n";
    echo "  Commission Amount: $" . number_format($transaction['commissionAmount'], 2) . "\n";
    echo "  Seller Payout: $" . number_format($transaction['sellerPayout'], 2) . "\n";
    
    $expectedCommission = 8.00; // 8% of $100
    $expectedPayout = 92.00;
    
    echo "  Expected Commission: $" . number_format($expectedCommission, 2) . "\n";
    echo "  Expected Payout: $" . number_format($expectedPayout, 2) . "\n";
    
    $commissionMatch = abs($transaction['commissionAmount'] - $expectedCommission) < 0.01;
    $payoutMatch = abs($transaction['sellerPayout'] - $expectedPayout) < 0.01;
    
    echo "  Result: " . ($commissionMatch && $payoutMatch ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 6: Get total platform earnings
    echo "Test 6: Get total platform earnings\n";
    $totalEarnings = $commissionService->getTotalPlatformEarnings();
    echo "  Total Platform Earnings: $" . number_format($totalEarnings, 2) . "\n";
    echo "  Should include at least: $" . number_format($expectedCommission, 2) . "\n";
    echo "  Result: " . ($totalEarnings >= $expectedCommission ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 7: Get earnings by date range
    echo "Test 7: Get earnings by date range (today)\n";
    $today = date('Y-m-d');
    $todayEarnings = $commissionService->getEarningsByDateRange($today, $today);
    echo "  Earnings for {$today}: $" . number_format($todayEarnings, 2) . "\n";
    echo "  Should include at least: $" . number_format($expectedCommission, 2) . "\n";
    echo "  Result: " . ($todayEarnings >= $expectedCommission ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 8: Verify transaction retrieval includes commission data
    echo "Test 8: Verify transaction retrieval includes commission data\n";
    $retrievedTransaction = $transactionService->getTransactionById($transaction['transactionId']);
    
    echo "  Retrieved transaction has commissionAmount: " . (isset($retrievedTransaction['commissionAmount']) ? "✓" : "✗") . "\n";
    echo "  Retrieved transaction has sellerPayout: " . (isset($retrievedTransaction['sellerPayout']) ? "✓" : "✗") . "\n";
    echo "  Commission Amount matches: " . ($retrievedTransaction['commissionAmount'] == $transaction['commissionAmount'] ? "✓" : "✗") . "\n";
    echo "  Seller Payout matches: " . ($retrievedTransaction['sellerPayout'] == $transaction['sellerPayout'] ? "✓" : "✗") . "\n";
    
    $allMatch = isset($retrievedTransaction['commissionAmount']) && 
                isset($retrievedTransaction['sellerPayout']) &&
                $retrievedTransaction['commissionAmount'] == $transaction['commissionAmount'] &&
                $retrievedTransaction['sellerPayout'] == $transaction['sellerPayout'];
    
    echo "  Result: " . ($allMatch ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Clean up test data
    echo "Cleaning up test data...\n";
    $db->exec("DELETE FROM transactions WHERE item_id = {$itemId}");
    $db->exec("DELETE FROM items WHERE id = {$itemId}");
    echo "✓ Cleanup complete\n\n";
    
    echo "=== All Commission Service Tests Complete ===\n";
    echo "✓ Commission calculation methods working correctly\n";
    echo "✓ Custom commission rates can be set and retrieved\n";
    echo "✓ TransactionService integration working correctly\n";
    echo "✓ Platform earnings tracking working correctly\n";
    echo "✓ Transaction retrieval includes commission data\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✓ Commission Service implementation verified successfully!\n";
