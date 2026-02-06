<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once 'src/Config/Database.php';
$db = App\Config\Database::getConnection();
$stmt = $db->query("SELECT * FROM items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($items);
