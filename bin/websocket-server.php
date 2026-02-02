#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\AuctionWebSocketServer;
use App\Config\Database;

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

try {
    // Get WebSocket configuration
    $wsHost = $_ENV['WS_HOST'] ?? '0.0.0.0';
    $wsPort = (int)($_ENV['WS_PORT'] ?? 8080);
    
    echo "Starting Auction WebSocket Server...\n";
    echo "Host: {$wsHost}\n";
    echo "Port: {$wsPort}\n";
    
    // Get database connection
    $db = Database::getConnection();
    echo "Database connection established\n";
    
    // Create WebSocket server
    $wsServer = new AuctionWebSocketServer($db);
    
    // Create Ratchet server
    $server = IoServer::factory(
        new HttpServer(
            new WsServer($wsServer)
        ),
        $wsPort,
        $wsHost
    );
    
    echo "WebSocket server started successfully!\n";
    echo "Listening on ws://{$wsHost}:{$wsPort}\n";
    echo "Press Ctrl+C to stop the server\n\n";
    
    // Run the server
    $server->run();
    
} catch (\Exception $e) {
    echo "Error starting WebSocket server: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
