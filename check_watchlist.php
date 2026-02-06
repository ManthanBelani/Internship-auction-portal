<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

echo "watchlist table structure:\n";
$result = $db->query("PRAGMA table_info(watchlist)");
foreach($result as $row) {
    echo "  " . $row['name'] . ' (' . $row['type'] . ")\n";
}

echo "\nTotal watchlist entries: ";
$count = $db->query("SELECT COUNT(*) as cnt FROM watchlist")->fetch();
echo $count['cnt'] . "\n";
