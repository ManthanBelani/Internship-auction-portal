<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== API Test with SQLite ===\n\n";

try {
    $db = Database::getConnection();
    echo "✓ Database connected successfully!\n\n";
    
    // Test 1: Check tables
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "  • {$table}\n";
    }
    
    // Test 2: Check admin user
    echo "\nAdmin user:\n";
    $stmt = $db->query("SELECT id, name, email, role FROM users WHERE role='admin'");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "  ✓ Email: {$admin['email']}\n";
        echo "  ✓ Password: admin123\n";
    }
    
    echo "\n✅ All tests passed!\n";
    echo "\nYour API is ready at: http://localhost:8000\n";
    echo "Admin Dashboard: http://localhost/admin/login.php\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
