<?php
// Check MySQL connection
echo "Checking MySQL connection...\n\n";

$host = 'localhost';
$port = '3306';
$dbname = 'auction_portal';
$username = 'root';
$password = '';

try {
    // Try to connect
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    echo "✓ MySQL connection successful!\n";
    echo "  Host: {$host}\n";
    echo "  Port: {$port}\n\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbname}'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '{$dbname}' exists\n\n";
        
        // Connect to database and check tables
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $username, $password);
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Tables in database ({$dbname}):\n";
        foreach ($tables as $table) {
            echo "  • {$table}\n";
        }
        echo "\nTotal tables: " . count($tables) . "\n";
    } else {
        echo "✗ Database '{$dbname}' does not exist\n";
        echo "\nTo create it, run:\n";
        echo "  mysql -u root -p -e \"CREATE DATABASE {$dbname}\"\n";
    }
    
} catch (PDOException $e) {
    echo "✗ MySQL connection failed!\n";
    echo "  Error: " . $e->getMessage() . "\n\n";
    echo "Make sure:\n";
    echo "  1. MySQL is running (start it in XAMPP Control Panel)\n";
    echo "  2. Port 3306 is not blocked\n";
    echo "  3. Username and password are correct\n";
}
