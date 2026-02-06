<?php
require_once 'vendor/autoload.php';
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
$db = new PDO("sqlite:{$dbPath}");
$stmt = $db->query("SELECT id, name, email, role, status FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
