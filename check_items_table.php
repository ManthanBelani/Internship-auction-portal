<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

// Check if items table exists
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='items'");
$exists = $result->fetch();

if ($exists) {
    echo "items table exists\n";
    $result = $db->query("PRAGMA table_info(items)");
    foreach($result as $row) {
        echo "  " . $row['name'] . ' (' . $row['type'] . ")\n";
    }
    
    // Count items
    $count = $db->query("SELECT COUNT(*) as cnt FROM items")->fetch();
    echo "\nTotal items: " . $count['cnt'] . "\n";
} else {
    echo "items table does NOT exist\n";
}
