<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getConnection();
    
    echo "Running role system migration...\n";
    
    $sql = file_get_contents(__DIR__ . '/migrations/011_add_role_to_users.sql');
    $db->exec($sql);
    
    echo "✓ Role system migration completed successfully\n";
    echo "✓ Default admin user created: admin@auction.com / admin123\n";
    echo "⚠ IMPORTANT: Change the admin password in production!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
