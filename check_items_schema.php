<?php
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $stmt = $pdo->query("PRAGMA table_info(items)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['name'] . " (" . $col['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
