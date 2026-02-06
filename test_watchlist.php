<?php
require_once 'vendor/autoload.php';

// Custom autoloader
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

use App\Config\Database;
use App\Services\WatchlistService;

$db = Database::getConnection();
$watchlistService = new WatchlistService($db);

// Test getting watchlist for user 2
echo "Getting watchlist for user 2:\n";
try {
    $watchlist = $watchlistService->getWatchlist(2);
    echo json_encode($watchlist, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
