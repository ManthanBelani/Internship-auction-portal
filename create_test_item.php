<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
use App\Models\Item;
use App\Config\Database;

$itemModel = new Item();
try {
    $item = $itemModel->create(
        1, 
        'Modern Luxury Villa', 
        'Beautiful villa with pool and ocean view. 5 bedrooms, 4 bathrooms.', 
        500000.00, 
        date('Y-m-d H:i:s', strtotime('+7 days'))
    );
    file_put_contents('debug_create.txt', "Item created successfully with ID: " . $item['id']);
} catch (Exception $e) {
    file_put_contents('debug_create.txt', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
