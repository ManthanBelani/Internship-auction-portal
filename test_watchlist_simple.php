<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

// Test query
$stmt = $db->prepare("
    SELECT w.id as watchlist_id, w.user_id, w.item_id, w.created_at as added_at,
           i.id, i.title, i.description, i.current_price, i.end_time, i.status,
           i.starting_price, i.seller_id
    FROM watchlist w
    JOIN items i ON w.item_id = i.id
    WHERE w.user_id = :user_id
    ORDER BY w.created_at DESC
");

$stmt->execute([':user_id' => 2]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Watchlist for user 2:\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
