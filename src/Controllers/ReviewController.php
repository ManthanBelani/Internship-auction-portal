<?php

namespace App\Controllers;

use App\Services\ReviewService;
use App\Utils\Response;
use App\Config\Database;

class ReviewController {
    private ReviewService $reviewService;

    public function __construct() {
        $db = Database::getConnection();
        $this->reviewService = new ReviewService($db);
    }

    /**
     * Create a new review
     * POST /api/reviews
     */
    public function create(array $data, int $userId): void {
        try {
            // Validate input
            $validator = new \App\Validation\Validator();
            $rules = [
                'transactionId' => 'required|integer',
                'revieweeId' => 'required|integer',
                'rating' => 'required|integer|min:1|max:5',
                'reviewText' => 'required|min:3'
            ];

            if (!$validator->validate($data, $rules)) {
                 Response::json(['error' => $validator->getFirstError()], 400);
                 return;
            }

            $transactionId = (int)$data['transactionId'];
            $revieweeId = (int)$data['revieweeId'];
            $rating = (int)$data['rating'];
            $reviewText = trim($data['reviewText']);

            // Create review
            $reviewId = $this->reviewService->createReview(
                $transactionId,
                $userId,
                $revieweeId,
                $rating,
                $reviewText
            );

            Response::json([
                'message' => 'Review created successfully',
                'reviewId' => $reviewId
            ], 201);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get all reviews for a user
     * GET /api/users/{userId}/reviews
     */
    public function getUserReviews(int $userId): void {
        try {
            $reviews = $this->reviewService->getReviewsForUser($userId);

            Response::json([
                'userId' => $userId,
                'totalReviews' => count($reviews),
                'reviews' => $reviews
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Get average rating for a user
     * GET /api/users/{userId}/rating
     */
    public function getUserRating(int $userId): void {
        try {
            $averageRating = $this->reviewService->calculateAverageRating($userId);
            $reviews = $this->reviewService->getReviewsForUser($userId);

            Response::json([
                'userId' => $userId,
                'averageRating' => $averageRating,
                'totalReviews' => count($reviews)
            ]);

        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 404);
        }
    }
}
