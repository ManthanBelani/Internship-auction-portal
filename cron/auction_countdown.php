<?php

/**
 * Cron job to send auction ending countdown notifications via WebSocket
 * 
 * Run this script periodically (e.g., every 30 seconds) using:
 * - Windows Task Scheduler
 * - Linux cron: */1 * * * * php /path/to/auction_countdown.php (runs every minute)
 * 
 * This script finds auctions ending within 5 minutes and broadcasts countdown updates
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Utils\WebSocketClient;

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Starting auction countdown check...\n";

try {
    $db = Database::getConnection();
    $wsClient = new WebSocketClient();
    
    // Find active auctions ending within 5 minutes
    $stmt = $db->prepare("
        SELECT item_id, end_time, title
        FROM items
        WHERE status = 'active'
        AND end_time > NOW()
        AND end_time <= DATE_ADD(NOW(), INTERVAL 5 MINUTE)
        ORDER BY end_time ASC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll();
    
    $notificationCount = 0;
    
    foreach ($items as $item) {
        $endTime = new DateTime($item['end_time']);
        $now = new DateTime();
        $secondsRemaining = $endTime->getTimestamp() - $now->getTimestamp();
        
        // Only send notifications for positive time remaining
        if ($secondsRemaining > 0) {
            echo "[{$timestamp}] Item #{$item['item_id']} '{$item['title']}' ending in {$secondsRemaining}s\n";
            
            // Send countdown notification
            $wsClient->notifyAuctionEnding((int)$item['item_id'], $secondsRemaining);
            $notificationCount++;
        }
    }
    
    echo "[{$timestamp}] Sent {$notificationCount} countdown notification(s)\n";
    
} catch (\Exception $e) {
    echo "[{$timestamp}] Error: " . $e->getMessage() . "\n";
    exit(1);
}
