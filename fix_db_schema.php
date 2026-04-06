<?php
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Updating database schema...\n";

    // Add category to items
    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN category TEXT");
        echo "✓ Added 'category' to items\n";
    } catch (Exception $e) {
        echo "- 'category' already exists or error: " . $e->getMessage() . "\n";
    }

    // Add columns to transactions
    $transColumns = [
        'tracking_number' => 'TEXT',
        'shipping_status' => "TEXT DEFAULT 'pending'",
        'payout_status' => "TEXT DEFAULT 'pending'",
        'payment_status' => "TEXT DEFAULT 'unpaid'"
    ];

    foreach ($transColumns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN $col $def");
            echo "✓ Added '$col' to transactions\n";
        } catch (Exception $e) {
            echo "- '$col' already exists or error: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Schema update complete!\n";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
