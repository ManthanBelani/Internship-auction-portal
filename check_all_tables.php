<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

$tables = ['items', 'item_images', 'watchlist'];

foreach ($tables as $table) {
    echo "\n=== $table table ===\n";
    try {
        $result = $db->query("PRAGMA table_info($table)");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            foreach($rows as $row) {
                echo "  " . $row['name'] . ' (' . $row['type'] . ")\n";
            }
        } else {
            echo "  Table exists but has no columns\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

