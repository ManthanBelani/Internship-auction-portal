<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'auction_portal';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "=== Running New Migrations ===\n\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to database '{$dbname}'\n\n";
    
    // New migrations to run
    $migrations = [
        '005_create_item_images_table.sql',
        '006_create_reviews_table.sql',
        '007_create_watchlist_table.sql',
        '008_alter_items_table_add_reserve_commission.sql',
        '009_alter_transactions_table_add_commission.sql'
    ];
    
    foreach ($migrations as $filename) {
        $file = __DIR__ . '/migrations/' . $filename;
        
        if (!file_exists($file)) {
            echo "✗ File not found: {$filename}\n";
            continue;
        }
        
        echo "Running migration: {$filename}... ";
        
        $sql = file_get_contents($file);
        
        try {
            $pdo->exec($sql);
            echo "✓ Success\n";
        } catch (PDOException $e) {
            echo "✗ Failed\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            
            // Continue with other migrations
        }
    }
    
    echo "\n=== Migration process completed ===\n";
    
    // Show all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nAll tables in database:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
