<?php
$dbPath = __DIR__ . '/database/auction_portal.sqlite';
try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Fixing database schema inconsistency...\n";

    // 1. Fix items table
    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN highest_bidder_id INTEGER");
        echo "✓ Added 'highest_bidder_id' to items\n";
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN reserve_met INTEGER DEFAULT 0");
        echo "✓ Added 'reserve_met' to items\n";
    } catch (Exception $e) {}

    // 2. Fix transactions table (Rename columns via temp table)
    try {
        $pdo->exec("PRAGMA foreign_keys=OFF");
        $pdo->beginTransaction();

        $pdo->exec("CREATE TABLE IF NOT EXISTS transactions_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            buyer_id INTEGER NOT NULL,
            seller_id INTEGER NOT NULL,
            final_price REAL NOT NULL,
            commission_amount REAL NOT NULL,
            seller_payout REAL NOT NULL,
            tracking_number TEXT,
            shipping_status TEXT DEFAULT 'pending',
            payout_status TEXT DEFAULT 'pending',
            payment_status TEXT DEFAULT 'unpaid',
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'cancelled')),
            completed_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Map old names to new names if they exist
        $cols = $pdo->query("PRAGMA table_info(transactions)")->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'name');

        $source_final_price = in_array('amount', $colNames) ? 'amount' : (in_array('final_price', $colNames) ? 'final_price' : '0');
        $source_commission = in_array('commission', $colNames) ? 'commission' : (in_array('commission_amount', $colNames) ? 'commission_amount' : '0');
        $source_payout = in_array('seller_earnings', $colNames) ? 'seller_earnings' : (in_array('seller_payout', $colNames) ? 'seller_payout' : '0');
        $source_date = in_array('created_at', $colNames) ? 'created_at' : (in_array('completed_at', $colNames) ? 'completed_at' : 'CURRENT_TIMESTAMP');

        $pdo->exec("INSERT INTO transactions_new (
            id, item_id, buyer_id, seller_id, final_price, commission_amount, seller_payout, 
            tracking_number, shipping_status, payout_status, payment_status, status, completed_at
        ) SELECT 
            id, item_id, buyer_id, seller_id, $source_final_price, $source_commission, $source_payout, 
            tracking_number, shipping_status, payout_status, payment_status, status, $source_date 
        FROM transactions");

        $pdo->exec("DROP TABLE transactions");
        $pdo->exec("ALTER TABLE transactions_new RENAME TO transactions");

        $pdo->commit();
        $pdo->exec("PRAGMA foreign_keys=ON");
        echo "✓ Migrated transactions table to correct column names\n";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "! Error migrating transactions: " . $e->getMessage() . "\n";
    }

    echo "\n✅ Schema fix complete!\n";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
