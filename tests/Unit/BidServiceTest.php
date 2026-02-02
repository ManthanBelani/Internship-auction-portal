<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\BidService;
use App\Services\ItemService;
use App\Services\UserService;
use App\Config\Database;
use DateTime;

class BidServiceTest extends TestCase
{
    private BidService $bidService;
    private ItemService $itemService;
    private UserService $userService;
    private array $testUsers = [];
    private array $testItems = [];

    protected function setUp(): void
    {
        $this->bidService = new BidService();
        $this->itemService = new ItemService();
        $this->userService = new UserService();
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        
        if (!empty($this->testItems)) {
            $ids = implode(',', $this->testItems);
            $db->exec("DELETE FROM bids WHERE item_id IN ($ids)");
            $db->exec("DELETE FROM transactions WHERE item_id IN ($ids)");
            $db->exec("DELETE FROM items WHERE id IN ($ids)");
        }
        
        if (!empty($this->testUsers)) {
            $ids = implode(',', $this->testUsers);
            $db->exec("DELETE FROM users WHERE id IN ($ids)");
        }
    }

    private function createTestUser($suffix = ''): array
    {
        $user = $this->userService->registerUser(
            'user_' . time() . rand() . $suffix . '@example.com',
            'Password123!',
            'Test User ' . $suffix
        );
        $this->testUsers[] = $user['userId'];
        return $user;
    }

    private function createTestItem($sellerId): array
    {
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
        $item = $this->itemService->createItem($sellerId, 'Test Item', 'Description', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];
        return $item;
    }

    public function testPlaceBid()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        $item = $this->createTestItem($seller['userId']);

        $bid = $this->bidService->placeBid($item['itemId'], $bidder['userId'], 150.00);

        $this->assertArrayHasKey('bidId', $bid);
        $this->assertEquals($item['itemId'], $bid['itemId']);
        $this->assertEquals($bidder['userId'], $bid['bidderId']);
        $this->assertEquals(150.00, $bid['amount']);
    }

    public function testBidUpdatesItemPrice()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        $item = $this->createTestItem($seller['userId']);

        $this->bidService->placeBid($item['itemId'], $bidder['userId'], 150.00);

        $updatedItem = $this->itemService->getItemById($item['itemId']);
        $this->assertEquals(150.00, $updatedItem['currentPrice']);
        $this->assertEquals($bidder['userId'], $updatedItem['highestBidderId']);
    }

    public function testLowBidRejection()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        $item = $this->createTestItem($seller['userId']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('higher than current price');
        
        $this->bidService->placeBid($item['itemId'], $bidder['userId'], 50.00);
    }

    public function testEqualBidRejection()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        $item = $this->createTestItem($seller['userId']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('higher than current price');
        
        $this->bidService->placeBid($item['itemId'], $bidder['userId'], 100.00);
    }

    public function testSelfBiddingPrevention()
    {
        $seller = $this->createTestUser('seller');
        $item = $this->createTestItem($seller['userId']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot bid on your own');
        
        $this->bidService->placeBid($item['itemId'], $seller['userId'], 150.00);
    }

    public function testBidOnExpiredAuction()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        
        // Create expired item directly in database
        $db = Database::getConnection();
        $pastTime = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO items (title, description, starting_price, current_price, end_time, seller_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute(['Expired', 'Test', 100.00, 100.00, $pastTime, $seller['userId']]);
        $itemId = (int)$db->lastInsertId();
        $this->testItems[] = $itemId;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('expired');
        
        $this->bidService->placeBid($itemId, $bidder['userId'], 150.00);
    }

    public function testBidOnNonActiveAuction()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        
        // Create completed item
        $db = Database::getConnection();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO items (title, description, starting_price, current_price, end_time, seller_id, status) VALUES (?, ?, ?, ?, ?, ?, 'completed')");
        $stmt->execute(['Completed', 'Test', 100.00, 100.00, $endTime, $seller['userId']]);
        $itemId = (int)$db->lastInsertId();
        $this->testItems[] = $itemId;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not active');
        
        $this->bidService->placeBid($itemId, $bidder['userId'], 150.00);
    }

    public function testNegativeBidRejection()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        $item = $this->createTestItem($seller['userId']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('positive');
        
        $this->bidService->placeBid($item['itemId'], $bidder['userId'], -50.00);
    }

    public function testZeroBidRejection()
    {
        $seller = $this->createTestUser('seller');
        $bidder = $this->createTestUser('bidder');
        $item = $this->createTestItem($seller['userId']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('positive');
        
        $this->bidService->placeBid($item['itemId'], $bidder['userId'], 0.00);
    }

    public function testMultipleBids()
    {
        $seller = $this->createTestUser('seller');
        $bidder1 = $this->createTestUser('bidder1');
        $bidder2 = $this->createTestUser('bidder2');
        $item = $this->createTestItem($seller['userId']);

        $bid1 = $this->bidService->placeBid($item['itemId'], $bidder1['userId'], 150.00);
        $bid2 = $this->bidService->placeBid($item['itemId'], $bidder2['userId'], 200.00);
        $bid3 = $this->bidService->placeBid($item['itemId'], $bidder1['userId'], 250.00);

        $this->assertEquals(150.00, $bid1['amount']);
        $this->assertEquals(200.00, $bid2['amount']);
        $this->assertEquals(250.00, $bid3['amount']);

        $item = $this->itemService->getItemById($item['itemId']);
        $this->assertEquals(250.00, $item['currentPrice']);
        $this->assertEquals($bidder1['userId'], $item['highestBidderId']);
    }

    public function testGetBidHistory()
    {
        $seller = $this->createTestUser('seller');
        $bidder1 = $this->createTestUser('bidder1');
        $bidder2 = $this->createTestUser('bidder2');
        $item = $this->createTestItem($seller['userId']);

        $this->bidService->placeBid($item['itemId'], $bidder1['userId'], 150.00);
        $this->bidService->placeBid($item['itemId'], $bidder2['userId'], 200.00);
        $this->bidService->placeBid($item['itemId'], $bidder1['userId'], 250.00);

        $history = $this->bidService->getBidHistory($item['itemId']);

        $this->assertCount(3, $history);
        $this->assertEquals(250.00, $history[0]['amount']); // Most recent first
        $this->assertEquals(200.00, $history[1]['amount']);
        $this->assertEquals(150.00, $history[2]['amount']);
    }

    public function testBidHistoryForNonExistentItem()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');
        
        $this->bidService->getBidHistory(999999);
    }

    public function testEmptyBidHistory()
    {
        $seller = $this->createTestUser('seller');
        $item = $this->createTestItem($seller['userId']);

        $history = $this->bidService->getBidHistory($item['itemId']);

        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }
}
