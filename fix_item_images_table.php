<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

// Drop the existing broken table
$db->exec("DROP TABLE IF EXISTS item_images");

// Create the correct table structure for SQLite
$db->exec("
CREATE TABLE IF NOT EXISTS item_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    image_url TEXT NOT NULL,
    thumbnail_url TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
)
");

echo "item_images table created successfully!\n";

// Verify the structure
$result = $db->query("PRAGMA table_info(item_images)");
echo "\nTable structure:\n";
foreach($result as $row) {
    echo "  " . $row['name'] . ' (' . $row['type'] . ")\n";
}
