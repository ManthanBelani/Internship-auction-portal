<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'auction_portal';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database: {$dbname}\n";

    $migrationsDir = __DIR__ . '/database/migrations';
    $files = scandir($migrationsDir);
    sort($files); // Ensure they run in order (001, 002, etc.)

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            echo "Running migration: {$file}...\n";
            $sql = file_get_contents($migrationsDir . '/' . $file);
            
            try {
                $pdo->exec($sql);
                echo "✓ Success\n";
            } catch (PDOException $e) {
                echo "✗ Failed: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\nAll migrations completed!\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
