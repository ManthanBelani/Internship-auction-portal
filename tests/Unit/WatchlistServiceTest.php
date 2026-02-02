<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\WatchlistService;
use App\Services\UserService;
use App\Models\Item;
use App\Config\Database;

class WatchlistServiceTest extends TestCase
{
    private WatchlistService $watchlistService;
    private UserService $userService;
    private Item $itemModel;
    private array $testUsers = [];
    private array $testItems = [];
    private array $testWatchlistEntries = [];

    protected function setUp(): void
    {
        $db = Database::getConnection();
        $this->watchlistService = new WatchlistService($db);
        $this->userService = new UserService();
        $this->itemModel = new Item();
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        
        // Clean up test watchlist entries
        if (!empty($this->testWatchlistEntries)) {
            foreach ($this->testWatchlistEntries as $entry) {
                $db->exec("DELETE FROM watchlist WHERE user_id = {$entry['userId']} AND item_id = {$entry['itemId']}");
            }
        }
        
        // Clean up test items
        if (!empty($this->testItems)) {
            $ids = implode(',', $this->testItems);
            $db->exec("DELETE FROM items WHERE id IN ($ids)");
        }
        
        // Clean up test users
        if (!empty($this->testUsers)) {
            $ids = implode(',', $this->testUsers);
            $db->exec("DELETE FROM users WHERE id IN ($ids)");
        }
    }

    private function createTestUser(string $suffix): array
    {
        $email = 'test_' . $suffix . '_' . time() . rand(1000, 9999) . '@example.com';
        $user = $this->userService->registerUser($email, 'Password123!', 'Test User ' . $suffix);
        $this->testUsers[] = $user['userId'];
        return $user;
    }

    private function createTestItem(int $sellerId, string $endTime = '+1 day'): int
    {
        $item = $this->itemModel->create(
            $sellerId,
            'Test Item ' . time() . rand(1000, 9999),
            'Test Description',
            100.00,
            date('Y-m-d H:i:s', strtotime($endTime))
        );
        $this->testItems[] = $item['id'];
        return $item['id'];
    }

    public function testAddToWatchlistSuccess()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        $itemId = $this->createTestItem($seller['userId']);
        
        $result = $this->watchlistService->addToWatchlist($user['userId'], $itemId);
        
        $this->assertTrue($result);
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId];
        
        // Verify it was added
        $this->assertTrue($this->watchlistService->isWatching($user['userId'], $itemId));
    }

    public function testAddToWatchlistDuplicatePrevention()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        $itemId = $this->createTestItem($seller['userId']);
        
        // Add first time
        $this->watchlistService->addToWatchlist($user['userId'], $itemId);
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId];
        
        // Attempt duplicate
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Item already in watchlist');
        
        $this->watchlistService->addToWatchlist($user['userId'], $itemId);
    }

    public function testAddToWatchlistNonExistentItem()
    {
        $user = $this->createTestUser('user');
        $nonExistentItemId = 999999;
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Item not found');
        
        $this->watchlistService->addToWatchlist($user['userId'], $nonExistentItemId);
    }

    public function testRemoveFromWatchlist()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        $itemId = $this->createTestItem($seller['userId']);
        
        // Add to watchlist
        $this->watchlistService->addToWatchlist($user['userId'], $itemId);
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId];
        
        // Verify it's there
        $this->assertTrue($this->watchlistService->isWatching($user['userId'], $itemId));
        
        // Remove from watchlist
        $result = $this->watchlistService->removeFromWatchlist($user['userId'], $itemId);
        
        $this->assertTrue($result);
        $this->assertFalse($this->watchlistService->isWatching($user['userId'], $itemId));
    }

    public function testIsWatching()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        $itemId = $this->createTestItem($seller['userId']);
        
        // Initially not watching
        $this->assertFalse($this->watchlistService->isWatching($user['userId'], $itemId));
        
        // Add to watchlist
        $this->watchlistService->addToWatchlist($user['userId'], $itemId);
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId];
        
        // Now watching
        $this->assertTrue($this->watchlistService->isWatching($user['userId'], $itemId));
    }

    public function testGetWatchlist()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        
        // Create and add multiple items to watchlist
        $itemId1 = $this->createTestItem($seller['userId']);
        $itemId2 = $this->createTestItem($seller['userId']);
        
        $this->watchlistService->addToWatchlist($user['userId'], $itemId1);
        $this->watchlistService->addToWatchlist($user['userId'], $itemId2);
        
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId1];
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId2];
        
        // Get watchlist
        $watchlist = $this->watchlistService->getWatchlist($user['userId']);
        
        $this->assertIsArray($watchlist);
        $this->assertCount(2, $watchlist);
        
        // Verify structure
        $this->assertArrayHasKey('watchlistId', $watchlist[0]);
        $this->assertArrayHasKey('userId', $watchlist[0]);
        $this->assertArrayHasKey('itemId', $watchlist[0]);
        $this->assertArrayHasKey('addedAt', $watchlist[0]);
        $this->assertArrayHasKey('item', $watchlist[0]);
        
        // Verify item details are included
        $this->assertIsArray($watchlist[0]['item']);
        $this->assertArrayHasKey('title', $watchlist[0]['item']);
        $this->assertArrayHasKey('currentPrice', $watchlist[0]['item']);
    }

    public function testGetWatchlistEmpty()
    {
        $user = $this->createTestUser('user');
        
        $watchlist = $this->watchlistService->getWatchlist($user['userId']);
        
        $this->assertIsArray($watchlist);
        $this->assertCount(0, $watchlist);
    }

    public function testGetEndingSoonItems()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        
        // Create items with different end times
        $itemEndingSoon = $this->createTestItem($seller['userId'], '+12 hours'); // Within 24 hours
        $itemEndingLater = $this->createTestItem($seller['userId'], '+48 hours'); // Beyond 24 hours
        $itemEndingVeryLater = $this->createTestItem($seller['userId'], '+7 days'); // Way beyond
        
        // Add all to watchlist
        $this->watchlistService->addToWatchlist($user['userId'], $itemEndingSoon);
        $this->watchlistService->addToWatchlist($user['userId'], $itemEndingLater);
        $this->watchlistService->addToWatchlist($user['userId'], $itemEndingVeryLater);
        
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemEndingSoon];
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemEndingLater];
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemEndingVeryLater];
        
        // Get items ending within 24 hours
        $endingSoon = $this->watchlistService->getEndingSoonItems($user['userId'], 24);
        
        $this->assertIsArray($endingSoon);
        $this->assertCount(1, $endingSoon);
        $this->assertEquals($itemEndingSoon, $endingSoon[0]['itemId']);
        
        // Verify structure includes item details
        $this->assertArrayHasKey('item', $endingSoon[0]);
        $this->assertArrayHasKey('title', $endingSoon[0]['item']);
        $this->assertArrayHasKey('endTime', $endingSoon[0]['item']);
        $this->assertArrayHasKey('sellerName', $endingSoon[0]['item']);
    }

    public function testGetEndingSoonItemsCustomThreshold()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        
        // Create item ending in 36 hours
        $itemId = $this->createTestItem($seller['userId'], '+36 hours');
        
        $this->watchlistService->addToWatchlist($user['userId'], $itemId);
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId];
        
        // Should not appear with 24 hour threshold
        $endingSoon24 = $this->watchlistService->getEndingSoonItems($user['userId'], 24);
        $this->assertCount(0, $endingSoon24);
        
        // Should appear with 48 hour threshold
        $endingSoon48 = $this->watchlistService->getEndingSoonItems($user['userId'], 48);
        $this->assertCount(1, $endingSoon48);
        $this->assertEquals($itemId, $endingSoon48[0]['itemId']);
    }

    public function testGetEndingSoonItemsOnlyActiveItems()
    {
        $user = $this->createTestUser('user');
        $seller = $this->createTestUser('seller');
        
        // Create item ending soon
        $itemId = $this->createTestItem($seller['userId'], '+12 hours');
        
        $this->watchlistService->addToWatchlist($user['userId'], $itemId);
        $this->testWatchlistEntries[] = ['userId' => $user['userId'], 'itemId' => $itemId];
        
        // Mark item as completed
        $db = Database::getConnection();
        $db->exec("UPDATE items SET status = 'completed' WHERE id = $itemId");
        
        // Should not appear in ending soon list
        $endingSoon = $this->watchlistService->getEndingSoonItems($user['userId'], 24);
        $this->assertCount(0, $endingSoon);
    }

    public function testMultipleUsersWatchingSameItem()
    {
        $user1 = $this->createTestUser('user1');
        $user2 = $this->createTestUser('user2');
        $seller = $this->createTestUser('seller');
        $itemId = $this->createTestItem($seller['userId']);
        
        // Both users add same item to watchlist
        $this->watchlistService->addToWatchlist($user1['userId'], $itemId);
        $this->watchlistService->addToWatchlist($user2['userId'], $itemId);
        
        $this->testWatchlistEntries[] = ['userId' => $user1['userId'], 'itemId' => $itemId];
        $this->testWatchlistEntries[] = ['userId' => $user2['userId'], 'itemId' => $itemId];
        
        // Both should be watching
        $this->assertTrue($this->watchlistService->isWatching($user1['userId'], $itemId));
        $this->assertTrue($this->watchlistService->isWatching($user2['userId'], $itemId));
        
        // Each should have it in their watchlist
        $watchlist1 = $this->watchlistService->getWatchlist($user1['userId']);
        $watchlist2 = $this->watchlistService->getWatchlist($user2['userId']);
        
        $this->assertCount(1, $watchlist1);
        $this->assertCount(1, $watchlist2);
    }
}
