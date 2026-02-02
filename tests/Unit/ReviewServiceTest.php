<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ReviewService;
use App\Services\UserService;
use App\Models\Item;
use App\Models\Transaction;
use App\Config\Database;

class ReviewServiceTest extends TestCase
{
    private ReviewService $reviewService;
    private UserService $userService;
    private Item $itemModel;
    private Transaction $transactionModel;
    private array $testUsers = [];
    private array $testItems = [];
    private array $testTransactions = [];
    private array $testReviews = [];

    protected function setUp(): void
    {
        $db = Database::getConnection();
        $this->reviewService = new ReviewService($db);
        $this->userService = new UserService();
        $this->itemModel = new Item();
        $this->transactionModel = new Transaction();
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        
        // Clean up test reviews
        if (!empty($this->testReviews)) {
            $ids = implode(',', $this->testReviews);
            $db->exec("DELETE FROM reviews WHERE review_id IN ($ids)");
        }
        
        // Clean up test transactions
        if (!empty($this->testTransactions)) {
            $ids = implode(',', $this->testTransactions);
            $db->exec("DELETE FROM transactions WHERE id IN ($ids)");
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
        $email = 'test_' . $suffix . '_' . time() . '@example.com';
        $user = $this->userService->registerUser($email, 'Password123!', 'Test User ' . $suffix);
        $this->testUsers[] = $user['userId'];
        return $user;
    }

    private function createTestItem(int $sellerId): int
    {
        $itemData = [
            'title' => 'Test Item ' . time(),
            'description' => 'Test Description',
            'starting_price' => 100.00,
            'end_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'seller_id' => $sellerId
        ];
        
        $item = $this->itemModel->create($itemData);
        $this->testItems[] = $item['id'];
        return $item['id'];
    }

    private function createTestTransaction(int $itemId, int $sellerId, int $buyerId): int
    {
        $transaction = $this->transactionModel->create($itemId, $sellerId, $buyerId, 150.00);
        $this->testTransactions[] = $transaction['id'];
        return $transaction['id'];
    }

    public function testCreateReviewSuccess()
    {
        // Create seller and buyer
        $seller = $this->createTestUser('seller');
        $buyer = $this->createTestUser('buyer');
        
        // Create item and transaction
        $itemId = $this->createTestItem($seller['userId']);
        $transactionId = $this->createTestTransaction($itemId, $seller['userId'], $buyer['userId']);
        
        // Create review
        $reviewId = $this->reviewService->createReview(
            $transactionId,
            $buyer['userId'],
            $seller['userId'],
            5,
            'Great seller!'
        );
        
        $this->testReviews[] = $reviewId;
        $this->assertIsInt($reviewId);
        $this->assertGreaterThan(0, $reviewId);
    }

    public function testInvalidRatingRejection()
    {
        $seller = $this->createTestUser('seller');
        $buyer = $this->createTestUser('buyer');
        $itemId = $this->createTestItem($seller['userId']);
        $transactionId = $this->createTestTransaction($itemId, $seller['userId'], $buyer['userId']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');
        
        $this->reviewService->createReview(
            $transactionId,
            $buyer['userId'],
            $seller['userId'],
            6,
            'Invalid rating'
        );
    }

    public function testDuplicateReviewPrevention()
    {
        $seller = $this->createTestUser('seller');
        $buyer = $this->createTestUser('buyer');
        $itemId = $this->createTestItem($seller['userId']);
        $transactionId = $this->createTestTransaction($itemId, $seller['userId'], $buyer['userId']);
        
        // Create first review
        $reviewId = $this->reviewService->createReview(
            $transactionId,
            $buyer['userId'],
            $seller['userId'],
            5,
            'First review'
        );
        $this->testReviews[] = $reviewId;
        
        // Attempt duplicate review
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already reviewed this transaction');
        
        $this->reviewService->createReview(
            $transactionId,
            $buyer['userId'],
            $seller['userId'],
            4,
            'Duplicate review'
        );
    }

    public function testUnauthorizedReviewRejection()
    {
        $seller = $this->createTestUser('seller');
        $buyer = $this->createTestUser('buyer');
        $outsider = $this->createTestUser('outsider');
        
        $itemId = $this->createTestItem($seller['userId']);
        $transactionId = $this->createTestTransaction($itemId, $seller['userId'], $buyer['userId']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not part of this transaction');
        
        $this->reviewService->createReview(
            $transactionId,
            $outsider['userId'],
            $seller['userId'],
            5,
            'Unauthorized review'
        );
    }

    public function testGetReviewsForUser()
    {
        $seller = $this->createTestUser('seller');
        $buyer1 = $this->createTestUser('buyer1');
        $buyer2 = $this->createTestUser('buyer2');
        
        // Create two transactions and reviews
        $itemId1 = $this->createTestItem($seller['userId']);
        $transactionId1 = $this->createTestTransaction($itemId1, $seller['userId'], $buyer1['userId']);
        $reviewId1 = $this->reviewService->createReview(
            $transactionId1,
            $buyer1['userId'],
            $seller['userId'],
            5,
            'Great seller!'
        );
        $this->testReviews[] = $reviewId1;
        
        $itemId2 = $this->createTestItem($seller['userId']);
        $transactionId2 = $this->createTestTransaction($itemId2, $seller['userId'], $buyer2['userId']);
        $reviewId2 = $this->reviewService->createReview(
            $transactionId2,
            $buyer2['userId'],
            $seller['userId'],
            4,
            'Good seller'
        );
        $this->testReviews[] = $reviewId2;
        
        // Get reviews for seller
        $reviews = $this->reviewService->getReviewsForUser($seller['userId']);
        
        $this->assertIsArray($reviews);
        $this->assertCount(2, $reviews);
        $this->assertEquals(5, $reviews[0]['rating']);
        $this->assertEquals(4, $reviews[1]['rating']);
    }

    public function testCalculateAverageRating()
    {
        $seller = $this->createTestUser('seller');
        $buyer1 = $this->createTestUser('buyer1');
        $buyer2 = $this->createTestUser('buyer2');
        
        // Create reviews with ratings 5 and 3 (average should be 4.0)
        $itemId1 = $this->createTestItem($seller['userId']);
        $transactionId1 = $this->createTestTransaction($itemId1, $seller['userId'], $buyer1['userId']);
        $reviewId1 = $this->reviewService->createReview(
            $transactionId1,
            $buyer1['userId'],
            $seller['userId'],
            5,
            'Excellent!'
        );
        $this->testReviews[] = $reviewId1;
        
        $itemId2 = $this->createTestItem($seller['userId']);
        $transactionId2 = $this->createTestTransaction($itemId2, $seller['userId'], $buyer2['userId']);
        $reviewId2 = $this->reviewService->createReview(
            $transactionId2,
            $buyer2['userId'],
            $seller['userId'],
            3,
            'Average'
        );
        $this->testReviews[] = $reviewId2;
        
        $avgRating = $this->reviewService->calculateAverageRating($seller['userId']);
        
        $this->assertEquals(4.0, $avgRating);
    }

    public function testCalculateAverageRatingNoReviews()
    {
        $user = $this->createTestUser('user');
        
        $avgRating = $this->reviewService->calculateAverageRating($user['userId']);
        
        $this->assertEquals(0.0, $avgRating);
    }

    public function testHasReviewed()
    {
        $seller = $this->createTestUser('seller');
        $buyer = $this->createTestUser('buyer');
        $itemId = $this->createTestItem($seller['userId']);
        $transactionId = $this->createTestTransaction($itemId, $seller['userId'], $buyer['userId']);
        
        // Before review
        $this->assertFalse($this->reviewService->hasReviewed($transactionId, $buyer['userId']));
        
        // Create review
        $reviewId = $this->reviewService->createReview(
            $transactionId,
            $buyer['userId'],
            $seller['userId'],
            5,
            'Great!'
        );
        $this->testReviews[] = $reviewId;
        
        // After review
        $this->assertTrue($this->reviewService->hasReviewed($transactionId, $buyer['userId']));
    }

    public function testCanReview()
    {
        $seller = $this->createTestUser('seller');
        $buyer = $this->createTestUser('buyer');
        $itemId = $this->createTestItem($seller['userId']);
        $transactionId = $this->createTestTransaction($itemId, $seller['userId'], $buyer['userId']);
        
        // Both seller and buyer can review
        $this->assertTrue($this->reviewService->canReview($transactionId, $seller['userId']));
        $this->assertTrue($this->reviewService->canReview($transactionId, $buyer['userId']));
    }
}
