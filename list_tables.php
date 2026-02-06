<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "Tables in database:\n";
foreach($result as $row) {
    echo "  - " . $row['name'] . "\n";
}
