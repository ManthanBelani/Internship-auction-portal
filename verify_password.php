<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Verifying password...\n\n";

$dbPath = __DIR__ . '/database/auction_portal.sqlite';
$db = new PDO("sqlite:{$dbPath}");

$stmt = $db->query("SELECT id, email, password FROM users WHERE email = 'admin@auction.com'");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "✗ User not found!\n";
    exit;
}

echo "User found:\n";
echo "  ID: {$user['id']}\n";
echo "  Email: {$user['email']}\n";
echo "  Password Hash: " . substr($user['password'], 0, 30) . "...\n\n";

$testPassword = 'admin123';
$isValid = password_verify($testPassword, $user['password']);

echo "Testing password '{$testPassword}':\n";
if ($isValid) {
    echo "  ✓ Password is correct!\n";
} else {
    echo "  ✗ Password is incorrect!\n";
    echo "\nGenerating new hash...\n";
    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
    echo "New hash: {$newHash}\n";
    
    echo "\nUpdating password in database...\n";
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$newHash, 'admin@auction.com']);
    echo "✓ Password updated!\n";
}
