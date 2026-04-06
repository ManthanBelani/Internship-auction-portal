<?php
require_once 'vendor/autoload.php';
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
$pdo = new PDO("sqlite:{$dbPath}");
$stmt = $pdo->query("PRAGMA table_info(users)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($columns);
