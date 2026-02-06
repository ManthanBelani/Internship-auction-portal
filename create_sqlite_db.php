<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Creating SQLite database with all tables...\n\n";

$dbPath = __DIR__ . '/database/auction_portal.sqlite';

// Create database file
$pdo = new PDO("sqlite:{$dbPath}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create all tables
$tables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            phone TEXT,
            role TEXT DEFAULT 'buyer' CHECK(role IN ('admin', 'moderator', 'seller', 'buyer')),
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'suspended', 'banned')),
            suspended_until TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ",
    'items' => "
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            starting_price REAL NOT NULL,
            current_price REAL NOT NULL,
            reserve_price REAL,
            commission_rate REAL DEFAULT 5.0,
            end_time TEXT NOT NULL,
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'sold', 'expired')),
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ",
    'bids' => "
        CREATE TABLE IF NOT EXISTS bids (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            bidder_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (bidder_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ",
    'transactions' => "
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            buyer_id INTEGER NOT NULL,
            seller_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            commission REAL NOT NULL,
            seller_earnings REAL NOT NULL,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'cancelled')),
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ",
    'item_images' => "
        CREATE TABLE IF NOT EXISTS item_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            thumbnail_path TEXT NOT NULL,
            is_primary INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
        )
    ",
    'reviews' => "
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reviewer_id INTEGER NOT NULL,
            reviewed_user_id INTEGER NOT NULL,
            transaction_id INTEGER,
            rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
        )
    ",
    'watchlist' => "
        CREATE TABLE IF NOT EXISTS watchlist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            item_id INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            UNIQUE(user_id, item_id)
        )
    ",
    'notifications' => "
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            event_type TEXT NOT NULL,
            event_data TEXT,
            event_id TEXT UNIQUE,
            delivered INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    "
];

foreach ($tables as $tableName => $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Created table: {$tableName}\n";
    } catch (PDOException $e) {
        echo "✗ Error creating {$tableName}: " . $e->getMessage() . "\n";
    }
}

// Create admin user
try {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Admin User',
        'admin@auction.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // admin123
        'admin',
        'active'
    ]);
    echo "\n✓ Created admin user (admin@auction.com / admin123)\n";
} catch (PDOException $e) {
    echo "\n✗ Error creating admin user: " . $e->getMessage() . "\n";
}

echo "\n✅ SQLite database setup complete!\n";
echo "Database location: {$dbPath}\n";
