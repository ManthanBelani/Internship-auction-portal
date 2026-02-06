<?php
// Simple server test script

echo "=== Auction Portal Server Test ===\n\n";

// 1. Check PHP version
echo "1. PHP Version: " . PHP_VERSION . "\n";

// 2. Check required extensions
echo "\n2. Required Extensions:\n";
$required = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json'];
foreach ($required as $ext) {
    $status = extension_loaded($ext) ? '✓ Installed' : '✗ Missing';
    echo "   - {$ext}: {$status}\n";
}

// 3. Check .env file
echo "\n3. Environment Configuration:\n";
if (file_exists('.env')) {
    echo "   ✓ .env file exists\n";
    require_once 'vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "   - DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
    echo "   - DB_NAME: " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";
    echo "   - DB_USER: " . ($_ENV['DB_USER'] ?? 'not set') . "\n";
} else {
    echo "   ✗ .env file not found\n";
}

// 4. Test database connection
echo "\n4. Database Connection:\n";
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'auction_portal';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    echo "   ✓ Database connection successful\n";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   - Tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "     • {$table}\n";
    }
} catch (PDOException $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "   → Make sure MySQL is running and database exists\n";
}

// 5. Check uploads directory
echo "\n5. File System:\n";
$uploadsDir = __DIR__ . '/uploads';
$thumbnailsDir = $uploadsDir . '/thumbnails';

if (is_dir($uploadsDir)) {
    echo "   ✓ uploads/ directory exists\n";
    echo "   - Writable: " . (is_writable($uploadsDir) ? 'Yes' : 'No') . "\n";
} else {
    echo "   ✗ uploads/ directory missing\n";
}

if (is_dir($thumbnailsDir)) {
    echo "   ✓ uploads/thumbnails/ directory exists\n";
    echo "   - Writable: " . (is_writable($thumbnailsDir) ? 'Yes' : 'No') . "\n";
} else {
    echo "   ✗ uploads/thumbnails/ directory missing\n";
}

// 6. Check API endpoint
echo "\n6. API Server Test:\n";
echo "   Server should be running at: http://localhost:8000\n";
echo "   Test with: http://localhost:8000/health\n";

echo "\n=== Test Complete ===\n";
