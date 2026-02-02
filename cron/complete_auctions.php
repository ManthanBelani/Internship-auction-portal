<?php

/**
 * Cron job to complete expired auctions
 * 
 * Run this script periodically (e.g., every minute) using:
 * - Windows Task Scheduler
 * - Linux cron: * * * * * php /path/to/complete_auctions.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\ItemService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Starting auction completion check...\n";

try {
    $itemService = new ItemService();
    $completedCount = $itemService->checkAndCompleteExpiredAuctions();
    
    echo "[{$timestamp}] Completed {$completedCount} auction(s)\n";
    
} catch (\Exception $e) {
    echo "[{$timestamp}] Error: " . $e->getMessage() . "\n";
    exit(1);
}
