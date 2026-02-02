<?php

namespace App\Models;

use PDO;
use Exception;

class Review {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create(int $transactionId, int $reviewerId, int $revieweeId, int $rating, string $reviewText): int {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Rating must be between 1 and 5");
        }

        $stmt = $this->db->prepare("
            INSERT INTO reviews (transaction_id, reviewer_id, reviewee_id, rating, review_text)
            VALUES (:transaction_id, :reviewer_id, :reviewee_id, :rating, :review_text)
        ");
        
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':reviewer_id' => $reviewerId,
            ':reviewee_id' => $revieweeId,
            ':rating' => $rating,
            ':review_text' => $reviewText
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function findByRevieweeId(int $revieweeId): array {
        $stmt = $this->db->prepare("
            SELECT r.review_id, r.transaction_id, r.reviewer_id, r.reviewee_id, 
                   r.rating, r.review_text, r.created_at,
                   u.name as reviewer_name
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.reviewee_id = :reviewee_id
            ORDER BY r.created_at DESC
        ");
        
        $stmt->execute([':reviewee_id' => $revieweeId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByTransactionId(int $transactionId): array {
        $stmt = $this->db->prepare("
            SELECT review_id, transaction_id, reviewer_id, reviewee_id, 
                   rating, review_text, created_at
            FROM reviews
            WHERE transaction_id = :transaction_id
        ");
        
        $stmt->execute([':transaction_id' => $transactionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $reviewId): ?array {
        $stmt = $this->db->prepare("
            SELECT review_id, transaction_id, reviewer_id, reviewee_id, 
                   rating, review_text, created_at
            FROM reviews
            WHERE review_id = :review_id
        ");
        
        $stmt->execute([':review_id' => $reviewId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function toArray(array $data): array {
        return [
            'reviewId' => (int) $data['review_id'],
            'transactionId' => (int) $data['transaction_id'],
            'reviewerId' => (int) $data['reviewer_id'],
            'revieweeId' => (int) $data['reviewee_id'],
            'rating' => (int) $data['rating'],
            'reviewText' => $data['review_text'],
            'createdAt' => $data['created_at'],
            'reviewerName' => $data['reviewer_name'] ?? null
        ];
    }
}
