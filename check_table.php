<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');
$result = $db->query('PRAGMA table_info(item_images)');
echo "item_images table structure:\n";
foreach($result as $row) {
    echo $row['name'] . ' - ' . $row['type'] . "\n";
}
