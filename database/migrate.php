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

echo "=== Database Migration Tool ===\n\n";

// First, connect without database to create it if needed
try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$dbname}' created or already exists\n\n";
    
    // Now connect to the specific database
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to database '{$dbname}'\n\n";
    
    // Get all migration files
    $migrationDir = __DIR__ . '/migrations';
    $files = glob($migrationDir . '/*.sql');
    sort($files);
    
    if (empty($files)) {
        echo "No migration files found\n";
        exit(1);
    }
    
    echo "Found " . count($files) . " migration file(s)\n\n";
    
    // Run each migration
    foreach ($files as $file) {
        $filename = basename($file);
        echo "Running migration: {$filename}... ";
        
        $sql = file_get_contents($file);
        
        try {
            $pdo->exec($sql);
            echo "✓ Success\n";
        } catch (PDOException $e) {
            echo "✗ Failed\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    echo "\n=== All migrations completed successfully! ===\n";
    
    // Show created tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nCreated tables:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
