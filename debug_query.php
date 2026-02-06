<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once 'src/Config/Database.php';
$db = App\Config\Database::getConnection();

echo "Checking findActive query...\n";
$sql = "SELECT i.*, u.name as seller_name 
        FROM items i 
        JOIN users u ON i.seller_id = u.id 
        WHERE i.status = 'active' AND i.end_time > CURRENT_TIMESTAMP";

$stmt = $db->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($items) . " items.\n";
if (count($items) == 0) {
    $now = $db->query("SELECT CURRENT_TIMESTAMP")->fetchColumn();
    echo "Current SQLite Timestamp: $now\n";
    $firstItem = $db->query("SELECT end_time FROM items LIMIT 1")->fetchColumn();
    echo "First Item End Time: $firstItem\n";
}
