<?php
$host = 'localhost';
$port = '3306';
$dbname = 'auction_portal';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database ({$dbname}):\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
