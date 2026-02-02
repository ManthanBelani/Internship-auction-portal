<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getConnection();
    
    echo "Running notification table migration...\n";
    
    $sql = file_get_contents(__DIR__ . '/migrations/010_create_notifications_table.sql');
    $db->exec($sql);
    
    echo "✓ Notifications table created successfully\n";
    
    // Verify table exists
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table verified\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
