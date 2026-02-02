<?php

namespace App\Services;

use PDO;
use Exception;
use App\Models\Review;

class ReviewService {
    private PDO $db;
    private Review $reviewModel;
    private const MIN_RATING = 1;
    private const MAX_RATING = 5;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->reviewModel = new Review($db);
    }

    /**
     * Validate that rating is between 1 and 5
     * 
     * @param int $rating Rating value to validate
     * @return bool True if valid
     * @throws Exception If rating is invalid
     */
    private function validateRating(int $rating): bool {
        if ($rating < self::MIN_RATING || $rating > self::MAX_RATING) {
            throw new Exception("Rating must be between " . self::MIN_RATING . " and " . self::MAX_RATING);
        }
        return true;
    }

    /**
     * Check if a user can review a transaction (must be part of the transaction)
     * 
     * @param int $transactionId Transaction ID
     * @param int $userId User ID attempting to review
     * @return bool True if user can review
     * @throws Exception If user cannot review
     */
    public function canReview(int $transactionId, int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT seller_id, buyer_id 
            FROM transactions 
            WHERE id = :transaction_id
        ");
        
        $stmt->execute([':transaction_id' => $transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found");
        }
        
        if ($transaction['seller_id'] != $userId && $transaction['buyer_id'] != $userId) {
            throw new Exception("User is not part of this transaction");
        }
        
        return true;
    }

    /**
     * Check if a user has already reviewed a transaction
     * 
     * @param int $transactionId Transaction ID
     * @param int $reviewerId Reviewer user ID
     * @return bool True if already reviewed
     */
    public function hasReviewed(int $transactionId, int $reviewerId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM reviews 
            WHERE transaction_id = :transaction_id AND reviewer_id = :reviewer_id
        ");
        
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':reviewer_id' => $reviewerId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Create a new review with validation
     * 
     * @param int $transactionId Transaction ID
     * @param int $reviewerId Reviewer user ID
     * @param int $revieweeId Reviewee user ID
     * @param int $rating Rating (1-5)
     * @param string $reviewText Review text
     * @return int Created review ID
     * @throws Exception If validation fails
     */
    public function createReview(int $transactionId, int $reviewerId, int $revieweeId, 
                                 int $rating, string $reviewText): int {
        // Validate rating
        $this->validateRating($rating);
        
        // Check if user can review this transaction
        $this->canReview($transactionId, $reviewerId);
        
        // Check for duplicate review
        if ($this->hasReviewed($transactionId, $reviewerId)) {
            throw new Exception("You have already reviewed this transaction");
        }
        
        // Create the review using the Review model
        return $this->reviewModel->create($transactionId, $reviewerId, $revieweeId, $rating, $reviewText);
    }

    /**
     * Get all reviews for a user
     * 
     * @param int $userId User ID
     * @return array Array of reviews
     */
    public function getReviewsForUser(int $userId): array {
        $reviews = $this->reviewModel->findByRevieweeId($userId);
        
        // Convert to proper format
        $result = [];
        foreach ($reviews as $review) {
            $result[] = $this->reviewModel->toArray($review);
        }
        
        return $result;
    }

    /**
     * Calculate average rating for a user
     * 
     * @param int $userId User ID
     * @return float Average rating (0.0 if no reviews)
     */
    public function calculateAverageRating(int $userId): float {
        $stmt = $this->db->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as count
            FROM reviews 
            WHERE reviewee_id = :user_id
        ");
        
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['count'] == 0) {
            return 0.0;
        }
        
        // Round to one decimal place
        return round((float) $result['avg_rating'], 1);
    }
}
