<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Services\ItemService;
use App\Services\BidService;
use App\Services\TransactionService;
use App\Config\Database;
use DateTime;

class FullWorkflowTest extends TestCase
{
    private UserService $userService;
    private ItemService $itemService;
    private BidService $bidService;
    private TransactionService $transactionService;
    private array $testUsers = [];
    private array $testItems = [];

    protected function setUp(): void
    {
        $this->userService = new UserService();
        $this->itemService = new ItemService();
        $this->bidService = new BidService();
        $this->transactionService = new TransactionService();
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

    public function testCompleteAuctionWorkflow()
    {
        // Step 1: Register seller
        $seller = $this->userService->registerUser(
            'seller_' . time() . '@example.com',
            'Password123!',
            'Test Seller'
        );
        $this->testUsers[] = $seller['userId'];
        $this->assertArrayHasKey('token', $seller);

        // Step 2: Register bidders
        $bidder1 = $this->userService->registerUser(
            'bidder1_' . time() . '@example.com',
            'Password123!',
            'Bidder One'
        );
        $this->testUsers[] = $bidder1['userId'];

        $bidder2 = $this->userService->registerUser(
            'bidder2_' . time() . '@example.com',
            'Password123!',
            'Bidder Two'
        );
        $this->testUsers[] = $bidder2['userId'];

        // Step 3: Seller creates auction item
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
        $item = $this->itemService->createItem(
            $seller['userId'],
            'Integration Test Item',
            'This is a test item for integration testing',
            100.00,
            $endTime
        );
        $this->testItems[] = $item['itemId'];
        $this->assertEquals(100.00, $item['startingPrice']);
        $this->assertEquals('active', $item['status']);

        // Step 4: Bidder 1 places first bid
        $bid1 = $this->bidService->placeBid($item['itemId'], $bidder1['userId'], 150.00);
        $this->assertEquals(150.00, $bid1['amount']);

        // Step 5: Bidder 2 places higher bid
        $bid2 = $this->bidService->placeBid($item['itemId'], $bidder2['userId'], 200.00);
        $this->assertEquals(200.00, $bid2['amount']);

        // Step 6: Bidder 1 places even higher bid
        $bid3 = $this->bidService->placeBid($item['itemId'], $bidder1['userId'], 250.00);
        $this->assertEquals(250.00, $bid3['amount']);

        // Step 7: Verify item current price updated
        $updatedItem = $this->itemService->getItemById($item['itemId']);
        $this->assertEquals(250.00, $updatedItem['currentPrice']);
        $this->assertEquals($bidder1['userId'], $updatedItem['highestBidderId']);
        $this->assertEquals(3, $updatedItem['bidCount']);

        // Step 8: Get bid history
        $bidHistory = $this->bidService->getBidHistory($item['itemId']);
        $this->assertCount(3, $bidHistory);
        $this->assertEquals(250.00, $bidHistory[0]['amount']); // Most recent first

        // Step 9: Simulate auction expiration
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE items SET end_time = NOW() - INTERVAL 1 HOUR WHERE id = ?");
        $stmt->execute([$item['itemId']]);

        // Step 10: Complete expired auctions
        $completedCount = $this->itemService->checkAndCompleteExpiredAuctions();
        $this->assertGreaterThanOrEqual(1, $completedCount);

        // Step 11: Verify auction marked as completed
        $completedItem = $this->itemService->getItemById($item['itemId']);
        $this->assertEquals('completed', $completedItem['status']);

        // Step 12: Verify transaction created
        $transactions = $this->transactionService->getUserTransactions($seller['userId']);
        $this->assertNotEmpty($transactions);
        
        $transaction = $transactions[0];
        $this->assertEquals($item['itemId'], $transaction['itemId']);
        $this->assertEquals($seller['userId'], $transaction['sellerId']);
        $this->assertEquals($bidder1['userId'], $transaction['buyerId']);
        $this->assertEquals(250.00, $transaction['finalPrice']);

        // Step 13: Verify buyer can see transaction
        $buyerTransactions = $this->transactionService->getUserTransactions($bidder1['userId']);
        $this->assertNotEmpty($buyerTransactions);
    }

    public function testAuctionWithoutBidsExpires()
    {
        // Create seller and item
        $seller = $this->userService->registerUser(
            'seller_nobids_' . time() . '@example.com',
            'Password123!',
            'Seller No Bids'
        );
        $this->testUsers[] = $seller['userId'];

        $endTime = (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');
        $item = $this->itemService->createItem(
            $seller['userId'],
            'No Bids Item',
            'This item will have no bids',
            100.00,
            $endTime
        );
        $this->testItems[] = $item['itemId'];

        // Expire the auction
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE items SET end_time = NOW() - INTERVAL 1 HOUR WHERE id = ?");
        $stmt->execute([$item['itemId']]);

        // Complete expired auctions
        $this->itemService->checkAndCompleteExpiredAuctions();

        // Verify item marked as expired (not completed)
        $expiredItem = $this->itemService->getItemById($item['itemId']);
        $this->assertEquals('expired', $expiredItem['status']);

        // Verify no transaction created
        $transactions = $this->transactionService->getUserTransactions($seller['userId']);
        $this->assertEmpty($transactions);
    }

    public function testMultipleBiddersCompeting()
    {
        // Create seller
        $seller = $this->userService->registerUser(
            'seller_compete_' . time() . '@example.com',
            'Password123!',
            'Competitive Seller'
        );
        $this->testUsers[] = $seller['userId'];

        // Create item
        $endTime = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
        $item = $this->itemService->createItem(
            $seller['userId'],
            'Competitive Item',
            'Many bidders will compete',
            50.00,
            $endTime
        );
        $this->testItems[] = $item['itemId'];

        // Create 5 bidders
        $bidders = [];
        for ($i = 1; $i <= 5; $i++) {
            $bidder = $this->userService->registerUser(
                "bidder{$i}_" . time() . rand() . '@example.com',
                'Password123!',
                "Bidder {$i}"
            );
            $this->testUsers[] = $bidder['userId'];
            $bidders[] = $bidder;
        }

        // Each bidder places increasing bids
        $currentPrice = 50.00;
        foreach ($bidders as $index => $bidder) {
            $bidAmount = $currentPrice + (($index + 1) * 10);
            $bid = $this->bidService->placeBid($item['itemId'], $bidder['userId'], $bidAmount);
            $this->assertEquals($bidAmount, $bid['amount']);
            $currentPrice = $bidAmount;
        }

        // Verify final state
        $finalItem = $this->itemService->getItemById($item['itemId']);
        $this->assertEquals(200.00, $finalItem['currentPrice']); // Last bid: 50 + (5 * 30) = 200
        $this->assertEquals($bidders[4]['userId'], $finalItem['highestBidderId']);
        $this->assertEquals(5, $finalItem['bidCount']);
    }
}
