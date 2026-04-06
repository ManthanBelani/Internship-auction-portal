<?php
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
if (!file_exists($dbPath)) {
    die("Database file not found at $dbPath\n");
}
try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- " . $table['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
