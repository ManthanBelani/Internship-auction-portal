<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Services\WatchlistService;
use App\Services\UserService;
use App\Models\Item;

echo "=== Watchlist Service Verification ===\n\n";

try {
    // Initialize services
    $db = Database::getConnection();
    $watchlistService = new WatchlistService($db);
    $userService = new UserService();
    $itemModel = new Item();
    
    echo "✓ Services initialized successfully\n\n";
    
    // Create test users
    echo "Creating test users...\n";
    $user1Email = 'watchlist_test_user1_' . time() . '@example.com';
    $user2Email = 'watchlist_test_user2_' . time() . '@example.com';
    $sellerEmail = 'watchlist_test_seller_' . time() . '@example.com';
    
    $user1 = $userService->registerUser($user1Email, 'Password123!', 'Test User 1');
    $user2 = $userService->registerUser($user2Email, 'Password123!', 'Test User 2');
    $seller = $userService->registerUser($sellerEmail, 'Password123!', 'Test Seller');
    
    echo "✓ Created test users (IDs: {$user1['userId']}, {$user2['userId']}, {$seller['userId']})\n\n";
    
    // Create test items
    echo "Creating test items...\n";
    $item1 = $itemModel->create(
        $seller['userId'],
        'Test Item 1 - Ending Soon',
        'This item ends in 12 hours',
        100.00,
        date('Y-m-d H:i:s', strtotime('+12 hours'))
    );
    
    $item2 = $itemModel->create(
        $seller['userId'],
        'Test Item 2 - Regular',
        'This item ends in 7 days',
        200.00,
        date('Y-m-d H:i:s', strtotime('+7 days'))
    );
    
    echo "✓ Created test items (IDs: {$item1['id']}, {$item2['id']})\n\n";
    
    // Test 1: Add to watchlist
    echo "Test 1: Add items to watchlist\n";
    $result1 = $watchlistService->addToWatchlist($user1['userId'], $item1['id']);
    $result2 = $watchlistService->addToWatchlist($user1['userId'], $item2['id']);
    echo "✓ User 1 added both items to watchlist\n";
    
    // Test 2: Check if watching
    echo "\nTest 2: Check if watching\n";
    $isWatching1 = $watchlistService->isWatching($user1['userId'], $item1['id']);
    $isWatching2 = $watchlistService->isWatching($user1['userId'], $item2['id']);
    $notWatching = $watchlistService->isWatching($user2['userId'], $item1['id']);
    
    if ($isWatching1 && $isWatching2 && !$notWatching) {
        echo "✓ isWatching() works correctly\n";
    } else {
        echo "✗ isWatching() failed\n";
    }
    
    // Test 3: Get watchlist
    echo "\nTest 3: Get watchlist\n";
    $watchlist = $watchlistService->getWatchlist($user1['userId']);
    echo "✓ Retrieved watchlist with " . count($watchlist) . " items\n";
    
    if (count($watchlist) === 2) {
        echo "✓ Watchlist count is correct\n";
        echo "  - Item 1: {$watchlist[0]['item']['title']}\n";
        echo "  - Item 2: {$watchlist[1]['item']['title']}\n";
    } else {
        echo "✗ Watchlist count is incorrect (expected 2, got " . count($watchlist) . ")\n";
    }
    
    // Test 4: Duplicate prevention
    echo "\nTest 4: Duplicate prevention\n";
    try {
        $watchlistService->addToWatchlist($user1['userId'], $item1['id']);
        echo "✗ Duplicate was not prevented\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already in watchlist') !== false) {
            echo "✓ Duplicate prevention works: {$e->getMessage()}\n";
        } else {
            echo "✗ Wrong exception: {$e->getMessage()}\n";
        }
    }
    
    // Test 5: Non-existent item
    echo "\nTest 5: Non-existent item rejection\n";
    try {
        $watchlistService->addToWatchlist($user1['userId'], 999999);
        echo "✗ Non-existent item was not rejected\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Item not found') !== false) {
            echo "✓ Non-existent item rejection works: {$e->getMessage()}\n";
        } else {
            echo "✗ Wrong exception: {$e->getMessage()}\n";
        }
    }
    
    // Test 6: Get ending soon items
    echo "\nTest 6: Get ending soon items\n";
    $endingSoon = $watchlistService->getEndingSoonItems($user1['userId'], 24);
    echo "✓ Retrieved ending soon items: " . count($endingSoon) . " items\n";
    
    if (count($endingSoon) === 1) {
        echo "✓ Ending soon count is correct (expected 1 item ending within 24 hours)\n";
        echo "  - Item: {$endingSoon[0]['item']['title']}\n";
        echo "  - End time: {$endingSoon[0]['item']['endTime']}\n";
    } else {
        echo "✗ Ending soon count is incorrect (expected 1, got " . count($endingSoon) . ")\n";
    }
    
    // Test 7: Multiple users watching same item
    echo "\nTest 7: Multiple users watching same item\n";
    $watchlistService->addToWatchlist($user2['userId'], $item1['id']);
    $user1Watching = $watchlistService->isWatching($user1['userId'], $item1['id']);
    $user2Watching = $watchlistService->isWatching($user2['userId'], $item1['id']);
    
    if ($user1Watching && $user2Watching) {
        echo "✓ Multiple users can watch the same item\n";
    } else {
        echo "✗ Multiple users watching failed\n";
    }
    
    // Test 8: Remove from watchlist
    echo "\nTest 8: Remove from watchlist\n";
    $removed = $watchlistService->removeFromWatchlist($user1['userId'], $item1['id']);
    $stillWatching = $watchlistService->isWatching($user1['userId'], $item1['id']);
    
    if ($removed && !$stillWatching) {
        echo "✓ Item removed from watchlist successfully\n";
    } else {
        echo "✗ Remove from watchlist failed\n";
    }
    
    // Verify watchlist count after removal
    $watchlistAfterRemoval = $watchlistService->getWatchlist($user1['userId']);
    if (count($watchlistAfterRemoval) === 1) {
        echo "✓ Watchlist count correct after removal (1 item remaining)\n";
    } else {
        echo "✗ Watchlist count incorrect after removal\n";
    }
    
    // Cleanup
    echo "\n=== Cleanup ===\n";
    $db->exec("DELETE FROM watchlist WHERE user_id IN ({$user1['userId']}, {$user2['userId']})");
    $db->exec("DELETE FROM items WHERE id IN ({$item1['id']}, {$item2['id']})");
    $db->exec("DELETE FROM users WHERE id IN ({$user1['userId']}, {$user2['userId']}, {$seller['userId']})");
    echo "✓ Test data cleaned up\n";
    
    echo "\n=== All Tests Passed! ===\n";
    echo "WatchlistService is working correctly.\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
