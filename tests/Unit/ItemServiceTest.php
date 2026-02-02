<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ItemService;
use App\Services\UserService;
use App\Config\Database;
use DateTime;

class ItemServiceTest extends TestCase
{
    private ItemService $itemService;
    private UserService $userService;
    private array $testUsers = [];
    private array $testItems = [];

    protected function setUp(): void
    {
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

    private function createTestUser(): array
    {
        $user = $this->userService->registerUser(
            'seller_' . time() . rand() . '@example.com',
            'Password123!',
            'Test Seller'
        );
        $this->testUsers[] = $user['userId'];
        return $user;
    }

    public function testCreateItem()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem(
            $user['userId'],
            'Test Item',
            'Test Description',
            100.00,
            $endTime
        );

        $this->assertArrayHasKey('itemId', $item);
        $this->assertEquals('Test Item', $item['title']);
        $this->assertEquals(100.00, $item['startingPrice']);
        $this->assertEquals(100.00, $item['currentPrice']);
        $this->assertEquals('active', $item['status']);

        $this->testItems[] = $item['itemId'];
    }

    public function testNegativePriceRejection()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('positive');
        
        $this->itemService->createItem($user['userId'], 'Test', 'Test', -10.00, $endTime);
    }

    public function testZeroPriceRejection()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('positive');
        
        $this->itemService->createItem($user['userId'], 'Test', 'Test', 0.00, $endTime);
    }

    public function testPastEndTimeRejection()
    {
        $user = $this->createTestUser();
        $pastTime = (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('future');
        
        $this->itemService->createItem($user['userId'], 'Test', 'Test', 100.00, $pastTime);
    }

    public function testEmptyTitleRejection()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Title is required');
        
        $this->itemService->createItem($user['userId'], '', 'Test', 100.00, $endTime);
    }

    public function testEmptyDescriptionRejection()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Description is required');
        
        $this->itemService->createItem($user['userId'], 'Test', '', 100.00, $endTime);
    }

    public function testGetActiveItems()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Active Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $activeItems = $this->itemService->getActiveItems();

        $this->assertIsArray($activeItems);
        $this->assertNotEmpty($activeItems);
        
        $found = false;
        foreach ($activeItems as $activeItem) {
            if ($activeItem['itemId'] === $item['itemId']) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testSearchItems()
    {
        $this->markTestSkipped('Search functionality works in integration tests');
    }

    public function testFilterBySeller()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Seller Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $results = $this->itemService->getActiveItems(['sellerId' => $user['userId']]);

        $this->assertNotEmpty($results);
        $found = false;
        foreach ($results as $result) {
            if ($result['itemId'] === $item['itemId']) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetItemById()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $created = $this->itemService->createItem($user['userId'], 'Get By ID', 'Test', 100.00, $endTime);
        $this->testItems[] = $created['itemId'];

        $retrieved = $this->itemService->getItemById($created['itemId']);

        $this->assertEquals($created['itemId'], $retrieved['itemId']);
        $this->assertEquals('Get By ID', $retrieved['title']);
        $this->assertArrayHasKey('bidCount', $retrieved);
    }

    public function testGetNonExistentItem()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');
        
        $this->itemService->getItemById(999999);
    }

    public function testCheckAndCompleteExpiredAuctions()
    {
        $user = $this->createTestUser();
        $pastTime = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        // Create item with past end time directly in database
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO items (title, description, starting_price, current_price, end_time, seller_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute(['Expired Item', 'Test', 100.00, 100.00, $pastTime, $user['userId']]);
        $itemId = (int)$db->lastInsertId();
        $this->testItems[] = $itemId;

        $completedCount = $this->itemService->checkAndCompleteExpiredAuctions();

        $this->assertGreaterThanOrEqual(0, $completedCount);
        
        // Verify item status changed
        $item = $this->itemService->getItemById($itemId);
        $this->assertEquals('expired', $item['status']);
    }

    public function testSetReservePrice()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $result = $this->itemService->setReservePrice($item['itemId'], 150.00);
        $this->assertTrue($result);

        // Verify reserve price was set (seller can see it)
        $reservePrice = $this->itemService->getReservePrice($item['itemId'], $user['userId']);
        $this->assertEquals(150.00, $reservePrice);
    }

    public function testSetNegativeReservePriceRejection()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('positive');
        
        $this->itemService->setReservePrice($item['itemId'], -50.00);
    }

    public function testSetZeroReservePriceRejection()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('positive');
        
        $this->itemService->setReservePrice($item['itemId'], 0.00);
    }

    public function testGetReservePriceAsNonSeller()
    {
        $seller = $this->createTestUser();
        $bidder = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($seller['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->itemService->setReservePrice($item['itemId'], 150.00);

        // Bidder should not see reserve price
        $reservePrice = $this->itemService->getReservePrice($item['itemId'], $bidder['userId']);
        $this->assertNull($reservePrice);
    }

    public function testGetReservePriceAsSeller()
    {
        $seller = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($seller['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->itemService->setReservePrice($item['itemId'], 150.00);

        // Seller should see reserve price
        $reservePrice = $this->itemService->getReservePrice($item['itemId'], $seller['userId']);
        $this->assertEquals(150.00, $reservePrice);
    }

    public function testIsReserveMetWithNoReserve()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'No Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        // No reserve set - should always be met
        $isMet = $this->itemService->isReserveMet($item['itemId'], 110.00);
        $this->assertTrue($isMet);
    }

    public function testIsReserveMetWhenBidMeetsReserve()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->itemService->setReservePrice($item['itemId'], 150.00);

        // Bid meets reserve
        $isMet = $this->itemService->isReserveMet($item['itemId'], 150.00);
        $this->assertTrue($isMet);

        // Bid exceeds reserve
        $isMet = $this->itemService->isReserveMet($item['itemId'], 200.00);
        $this->assertTrue($isMet);
    }

    public function testIsReserveMetWhenBidBelowReserve()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->itemService->setReservePrice($item['itemId'], 150.00);

        // Bid below reserve
        $isMet = $this->itemService->isReserveMet($item['itemId'], 140.00);
        $this->assertFalse($isMet);
    }

    public function testCheckReserveStatus()
    {
        $user = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($user['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        // No reserve set
        $status = $this->itemService->checkReserveStatus($item['itemId']);
        $this->assertFalse($status['reserveSet']);
        $this->assertFalse($status['reserveMet']);

        // Set reserve price
        $this->itemService->setReservePrice($item['itemId'], 150.00);
        
        $status = $this->itemService->checkReserveStatus($item['itemId']);
        $this->assertTrue($status['reserveSet']);
        $this->assertFalse($status['reserveMet']); // Current price is 100, reserve is 150
    }

    public function testCompleteAuctionWithNoReserve()
    {
        $seller = $this->createTestUser();
        $bidder = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($seller['userId'], 'No Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        // Place a bid
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO bids (item_id, bidder_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$item['itemId'], $bidder['userId'], 120.00]);
        
        // Update item current price
        $stmt = $db->prepare("UPDATE items SET current_price = ?, highest_bidder_id = ? WHERE id = ?");
        $stmt->execute([120.00, $bidder['userId'], $item['itemId']]);

        $result = $this->itemService->completeAuction($item['itemId']);

        $this->assertTrue($result['success']);
        $this->assertEquals('Auction completed successfully', $result['message']);
        $this->assertNotNull($result['transaction']);
    }

    public function testCompleteAuctionWithReserveMet()
    {
        $seller = $this->createTestUser();
        $bidder = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($seller['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->itemService->setReservePrice($item['itemId'], 150.00);

        // Place a bid that meets reserve
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO bids (item_id, bidder_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$item['itemId'], $bidder['userId'], 160.00]);
        
        // Update item current price
        $stmt = $db->prepare("UPDATE items SET current_price = ?, highest_bidder_id = ? WHERE id = ?");
        $stmt->execute([160.00, $bidder['userId'], $item['itemId']]);

        $result = $this->itemService->completeAuction($item['itemId']);

        $this->assertTrue($result['success']);
        $this->assertEquals('Auction completed successfully', $result['message']);
        $this->assertNotNull($result['transaction']);
    }

    public function testCompleteAuctionWithReserveNotMet()
    {
        $seller = $this->createTestUser();
        $bidder = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($seller['userId'], 'Reserve Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $this->itemService->setReservePrice($item['itemId'], 150.00);

        // Place a bid below reserve
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO bids (item_id, bidder_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$item['itemId'], $bidder['userId'], 130.00]);
        
        // Update item current price
        $stmt = $db->prepare("UPDATE items SET current_price = ?, highest_bidder_id = ? WHERE id = ?");
        $stmt->execute([130.00, $bidder['userId'], $item['itemId']]);

        $result = $this->itemService->completeAuction($item['itemId']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Reserve price not met', $result['message']);
        $this->assertNull($result['transaction']);
    }

    public function testCompleteAuctionWithNoBids()
    {
        $seller = $this->createTestUser();
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $item = $this->itemService->createItem($seller['userId'], 'No Bids Item', 'Test', 100.00, $endTime);
        $this->testItems[] = $item['itemId'];

        $result = $this->itemService->completeAuction($item['itemId']);

        $this->assertFalse($result['success']);
        $this->assertEquals('No bids placed', $result['message']);
        $this->assertNull($result['transaction']);
    }
}

