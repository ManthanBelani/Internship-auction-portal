<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Checking users in database...\n\n";

$dbPath = __DIR__ . '/database/auction_portal.sqlite';
$db = new PDO("sqlite:{$dbPath}");

$stmt = $db->query("SELECT id, name, email, role, status FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) === 0) {
    echo "✗ No users found in database!\n";
    echo "\nCreating admin user...\n";
    
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Admin User',
        'admin@auction.com',
        password_hash('admin123', PASSWORD_DEFAULT),
        'admin',
        'active'
    ]);
    
    echo "✓ Admin user created!\n";
    
    // Fetch again
    $stmt = $db->query("SELECT id, name, email, role, status FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo "Users in database:\n";
foreach ($users as $user) {
    echo "  • ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}, Status: {$user['status']}\n";
}

echo "\nTotal users: " . count($users) . "\n";
