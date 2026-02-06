<?php
$db = new PDO('sqlite:database/auction_portal.sqlite');

// Simulate what the API returns
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

echo "Raw query results:\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n\n";

// Transform like the model does
$transformed = [];
foreach ($results as $data) {
    $transformed[] = [
        'watchlistId' => (int) $data['watchlist_id'],
        'userId' => (int) $data['user_id'],
        'itemId' => (int) $data['item_id'],
        'addedAt' => $data['added_at'],
        'id' => (string) $data['id'],
        'title' => $data['title'],
        'description' => $data['description'],
        'startingPrice' => (float) $data['starting_price'],
        'currentPrice' => (float) $data['current_price'],
        'endTime' => $data['end_time'],
        'status' => $data['status'],
        'sellerId' => (int) $data['seller_id'],
        'bidCount' => 0,
        'images' => []
    ];
}

echo "Transformed results (what API returns):\n";
echo json_encode($transformed, JSON_PRETTY_PRINT) . "\n";
