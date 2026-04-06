<?php

namespace App\Controllers;

use App\Services\AnalyticsService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class AnalyticsController
{
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * GET /api/seller/analytics/overview
     * Get analytics overview
     */
    public function getOverview(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $overview = $this->analyticsService->getOverview((int)$user['userId']);
            Response::success($overview);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller/analytics/revenue
     * Get revenue analytics
     */
    public function getRevenueAnalytics(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $period = $_GET['period'] ?? 'month';
            $analytics = $this->analyticsService->getRevenueAnalytics((int)$user['userId'], $period);
            
            Response::success($analytics);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller/analytics/performance
     * Get performance metrics
     */
    public function getPerformance(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $performance = $this->analyticsService->getPerformanceMetrics((int)$user['userId']);
            Response::success($performance);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller/analytics/categories
     * Get category performance
     */
    public function getCategoryAnalytics(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $categories = $this->analyticsService->getCategoryPerformance((int)$user['userId']);
            Response::success(['categories' => $categories]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}
