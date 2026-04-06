<?php
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
