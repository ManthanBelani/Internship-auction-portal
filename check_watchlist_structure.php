<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

echo "watchlist table structure:\n";
$result = $db->query("PRAGMA table_info(watchlist)");
foreach($result as $row) {
    echo "  " . $row['name'] . ' (' . $row['type'] . ') - PK: ' . $row['pk'] . "\n";
}

echo "\nSample data:\n";
$data = $db->query("SELECT * FROM watchlist LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach($data as $row) {
    echo "  " . json_encode($row) . "\n";
}
