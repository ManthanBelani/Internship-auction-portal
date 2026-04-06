<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'auction_portal';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "=== BidOrbit MySQL Database Seeder ===\n\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Connected to MySQL database '{$dbname}'\n\n";

    echo "Clearing existing data...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = ['events', 'bids', 'item_images', 'watchlist', 'reviews', 'notifications', 'messages',
               'payouts', 'orders', 'transactions', 'items', 'payment_methods', 'shipping_addresses'];

    foreach ($tables as $table) {
        try {
            $pdo->exec("DELETE FROM `{$table}`");
            echo "  - Cleared {$table}\n";
        } catch (\Exception $e) {
            echo "  - Skipped {$table}: " . $e->getMessage() . "\n";
        }
    }

    $pdo->exec("DELETE FROM users");
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    echo "  - Cleared users (auto_increment reset)\n";

    foreach ($tables as $table) {
        try {
            $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        } catch (\Exception $e) {}
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n";

    // ===================== USERS =====================
    echo "Seeding users...\n";
    $users = [
        ['John Buyer', 'buyer@bidorbit.com', password_hash('buyer123', PASSWORD_DEFAULT), 'buyer', 'active'],
        ['Sarah Collector', 'sarah@bidorbit.com', password_hash('sarah123', PASSWORD_DEFAULT), 'buyer', 'active'],
        ['Mike Bidder', 'mike@bidorbit.com', password_hash('mike123', PASSWORD_DEFAULT), 'buyer', 'active'],
        ['Emily Chen', 'emily@bidorbit.com', password_hash('emily123', PASSWORD_DEFAULT), 'buyer', 'active'],
        ['Jane Seller', 'seller@bidorbit.com', password_hash('seller123', PASSWORD_DEFAULT), 'seller', 'active'],
        ['Tech Deals Store', 'techdeals@bidorbit.com', password_hash('techdeals123', PASSWORD_DEFAULT), 'seller', 'active'],
        ['Luxury Auctions', 'luxury@bidorbit.com', password_hash('luxury123', PASSWORD_DEFAULT), 'seller', 'active'],
        ['Vintage Finds', 'vintage@bidorbit.com', password_hash('vintage123', PASSWORD_DEFAULT), 'seller', 'active'],
        ['Admin User', 'admin@bidorbit.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'active'],
        ['Mod User', 'mod@bidorbit.com', password_hash('mod123', PASSWORD_DEFAULT), 'moderator', 'active'],
    ];

    $userStmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role, status, registered_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())
    ");

    foreach ($users as $u) {
        $userStmt->execute($u);
        echo "  - {$u[1]} ({$u[3]})\n";
    }

    // user IDs: 1=John, 2=Sarah, 3=Mike, 4=Emily, 5=Jane, 6=TechDeals, 7=Luxury, 8=Vintage, 9=Admin, 10=Mod

    // ===================== ITEMS =====================
    echo "\nSeeding items...\n";

    $now = new DateTime();
    $future7 = (clone $now)->modify('+7 days')->format('Y-m-d H:i:s');
    $future3 = (clone $now)->modify('+3 days')->format('Y-m-d H:i:s');
    $future1 = (clone $now)->modify('+1 day')->format('Y-m-d H:i:s');
    $future14 = (clone $now)->modify('+14 days')->format('Y-m-d H:i:s');
    $future5 = (clone $now)->modify('+5 days')->format('Y-m-d H:i:s');
    $future10 = (clone $now)->modify('+10 days')->format('Y-m-d H:i:s');
    $past7 = (clone $now)->modify('-7 days')->format('Y-m-d H:i:s');
    $past14 = (clone $now)->modify('-14 days')->format('Y-m-d H:i:s');
    $past30 = (clone $now)->modify('-30 days')->format('Y-m-d H:i:s');

    $items = [
        // ACTIVE items from Jane Seller (user 5)
        [5, 'iPhone 15 Pro Max 256GB', 'Brand new sealed iPhone 15 Pro Max, Natural Titanium, 256GB. Factory unlocked with all accessories.', 'Electronics', 999.99, 1150.00, NULL, 1, 1, 0.05, $future7, 'active'],
        [5, 'Vintage Rolex Submariner 1968', 'Authentic 1968 Rolex Submariner ref. 5513. Excellent patina, recently serviced. Comes with original box and papers.', 'Jewelry', 5000.00, 6200.00, 5500.00, 2, 1, 0.05, $future3, 'active'],
        [5, 'Original Banksy Screen Print', 'Authenticated Banksy screen print "Girl with Balloon". Certificate of authenticity from Pest Control.', 'Art', 25000.00, 32000.00, 28000.00, 3, 1, 0.05, $future5, 'active'],
        [5, 'Hermes Birkin Bag 25 Togo', 'Authentic Hermes Birkin 25 in Gold Togo leather with gold hardware. Store fresh condition.', 'Fashion', 12000.00, 14500.00, 13000.00, 1, 1, 0.05, $future10, 'active'],

        // ACTIVE items from Tech Deals Store (user 6)
        [6, 'MacBook Pro 16" M3 Max', 'Like new MacBook Pro M3 Max, 36GB RAM, 1TB SSD. AppleCare+ until 2026. Includes original box.', 'Electronics', 1899.00, 2100.00, NULL, 1, 1, 0.05, $future14, 'active'],
        [6, 'Sony PlayStation 5 Pro Bundle', 'PS5 Pro console with DualSense Edge, 2 controllers, and 5 games. Sealed in box.', 'Electronics', 699.99, 820.00, NULL, 3, 1, 0.05, $future7, 'active'],
        [6, 'NVIDIA RTX 4090 Founders Edition', 'Brand new RTX 4090 FE, sealed. With receipt and full warranty.', 'Electronics', 1599.00, 1750.00, NULL, 2, 1, 0.05, $future1, 'active'],
        [6, 'Samsung 85" Neo QLED 8K TV', 'Samsung QN900C 85 inch 8K Neo QLED. Wall mount included. Open box, never mounted.', 'Electronics', 3499.00, 3650.00, 3500.00, 4, 1, 0.05, $future5, 'active'],

        // ACTIVE items from Luxury Auctions (user 7)
        [7, 'Patek Philippe Nautilus 5711', 'Patek Philippe Nautilus ref. 5711/1A-011. Discontinued model, full set with box and papers.', 'Jewelry', 85000.00, 95000.00, 90000.00, 2, 1, 0.05, $future14, 'active'],
        [7, 'Louis Vuitton Steamer Trunk', 'Vintage 1920s Louis Vuitton Steamer Trunk. Excellent condition for its age. Rare collector piece.', 'Fashion', 8000.00, 9500.00, 8500.00, 1, 1, 0.05, $future7, 'active'],
        [7, 'Diamond Necklace 2.5ct', '18K white gold necklace with 2.5ct VS1 G-color diamond. GIA certified.', 'Jewelry', 15000.00, 17200.00, 16000.00, 4, 1, 0.05, $future3, 'active'],

        // ACTIVE items from Vintage Finds (user 8)
        [8, '1967 Ford Mustang Fastback', 'Fully restored 1967 Ford Mustang Fastback. 289 V8, 4-speed manual. Concours quality restoration.', 'Vehicles', 35000.00, 42000.00, 38000.00, 3, 1, 0.05, $future10, 'active'],
        [8, 'Vintage Gibson Les Paul 1959', '1959 Gibson Les Paul Standard Reissue. Cherry Sunburst, all original electronics. Plays and sounds incredible.', 'Music', 12000.00, 14800.00, 13000.00, 1, 1, 0.05, $future5, 'active'],
        [8, 'First Edition Harry Potter', 'UK First Edition Harry Potter and the Philosophers Stone. Excellent condition with dust jacket.', 'Books', 2500.00, 3100.00, 2800.00, 2, 1, 0.05, $future7, 'active'],
        [8, 'Nike Air Jordan 1 Chicago 1985', 'Deadstock original 1985 Air Jordan 1 Chicago. Size 10. Never worn, original box.', 'Fashion', 8000.00, 9800.00, 9000.00, 4, 1, 0.05, $future1, 'active'],

        // COMPLETED items (past end_time)
        [5, 'Canon EOS R5 Camera Body', 'Canon EOS R5 with 45MP sensor, 8K video. Low shutter count (3200). Includes extra battery.', 'Electronics', 2800.00, 3400.00, NULL, 1, 1, 0.05, $past7, 'completed'],
        [6, 'iPad Pro 12.9" M2 256GB', 'iPad Pro 12.9 inch with M2 chip, 256GB WiFi + Cellular. Like new with Apple Pencil 2.', 'Electronics', 899.00, 1050.00, NULL, 2, 1, 0.05, $past14, 'completed'],
        [7, 'Rolex Daytona Ceramic', 'Rolex Cosmograph Daytona ref. 116500LN. Black ceramic bezel. Full set 2022.', 'Jewelry', 28000.00, 31500.00, 30000.00, 3, 1, 0.05, $past30, 'completed'],
        [8, 'Fender Stratocaster 1963', '1963 Fender Stratocaster in Olympic White. All original parts including case. Exceptional tone.', 'Music', 18000.00, 22000.00, 20000.00, 1, 1, 0.05, $past14, 'completed'],

        // EXPIRED items (reserve not met)
        [5, 'Rare Comic Book Collection', 'Complete set of Amazing Spider-Man #1-50. Various conditions, mostly VG to Fine.', 'Collectibles', 15000.00, 12500.00, 18000.00, 2, 0, 0.05, $past7, 'expired'],
        [6, 'Vintage Wine Collection (12 bottles)', 'Assorted vintage wines from Bordeaux and Burgundy. 1990-2010 vintages. Properly stored.', 'Collectibles', 3000.00, 2400.00, 3500.00, 3, 0, 0.05, $past14, 'expired'],
    ];

    $itemStmt = $pdo->prepare("
        INSERT INTO items (seller_id, title, description, category, starting_price, current_price, reserve_price, highest_bidder_id, reserve_met, commission_rate, end_time, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($items as $item) {
        $itemStmt->execute($item);
        echo "  - [{$item[11]}] {$item[1]} - \${$item[5]}\n";
    }

    // item IDs: 1-14 active, 15-18 completed, 19-20 expired

    // ===================== BIDS =====================
    echo "\nSeeding bids...\n";
    $bids = [];
    $bidTime = clone $now;

    // Item 1 (iPhone) - 3 bidders
    $bids[] = [1, 1, 1020.00, (clone $bidTime)->modify('-6 days')->format('Y-m-d H:i:s')];
    $bids[] = [2, 1, 1080.00, (clone $bidTime)->modify('-5 days')->format('Y-m-d H:i:s')];
    $bids[] = [1, 1, 1150.00, (clone $bidTime)->modify('-4 days')->format('Y-m-d H:i:s')];

    // Item 2 (Rolex) - 4 bidders
    $bids[] = [2, 2, 5200.00, (clone $bidTime)->modify('-2 days')->format('Y-m-d H:i:s')];
    $bids[] = [3, 2, 5500.00, (clone $bidTime)->modify('-2 days')->modify('+2 hours')->format('Y-m-d H:i:s')];
    $bids[] = [1, 2, 5800.00, (clone $bidTime)->modify('-1 day')->format('Y-m-d H:i:s')];
    $bids[] = [2, 2, 6200.00, (clone $bidTime)->modify('-12 hours')->format('Y-m-d H:i:s')];

    // Item 3 (Banksy) - 3 bidders
    $bids[] = [3, 3, 27000.00, (clone $bidTime)->modify('-4 days')->format('Y-m-d H:i:s')];
    $bids[] = [4, 3, 29000.00, (clone $bidTime)->modify('-3 days')->format('Y-m-d H:i:s')];
    $bids[] = [3, 3, 32000.00, (clone $bidTime)->modify('-2 days')->format('Y-m-d H:i:s')];

    // Item 4 (Birkin) - 2 bidders
    $bids[] = [1, 4, 13000.00, (clone $bidTime)->modify('-8 days')->format('Y-m-d H:i:s')];
    $bids[] = [4, 4, 14500.00, (clone $bidTime)->modify('-6 days')->format('Y-m-d H:i:s')];

    // Item 5 (MacBook) - 2 bidders
    $bids[] = [1, 5, 1950.00, (clone $bidTime)->modify('-10 days')->format('Y-m-d H:i:s')];
    $bids[] = [3, 5, 2100.00, (clone $bidTime)->modify('-8 days')->format('Y-m-d H:i:s')];

    // Item 6 (PS5) - 3 bidders
    $bids[] = [3, 6, 740.00, (clone $bidTime)->modify('-5 days')->format('Y-m-d H:i:s')];
    $bids[] = [1, 6, 780.00, (clone $bidTime)->modify('-4 days')->format('Y-m-d H:i:s')];
    $bids[] = [3, 6, 820.00, (clone $bidTime)->modify('-3 days')->format('Y-m-d H:i:s')];

    // Item 7 (RTX 4090) - 2 bidders
    $bids[] = [2, 7, 1680.00, (clone $bidTime)->modify('-20 hours')->format('Y-m-d H:i:s')];
    $bids[] = [3, 7, 1750.00, (clone $bidTime)->modify('-10 hours')->format('Y-m-d H:i:s')];

    // Item 8 (Samsung TV) - 2 bidders
    $bids[] = [4, 8, 3550.00, (clone $bidTime)->modify('-3 days')->format('Y-m-d H:i:s')];
    $bids[] = [1, 8, 3650.00, (clone $bidTime)->modify('-2 days')->format('Y-m-d H:i:s')];

    // Item 9 (Patek) - 3 bidders
    $bids[] = [2, 9, 88000.00, (clone $bidTime)->modify('-10 days')->format('Y-m-d H:i:s')];
    $bids[] = [4, 9, 91000.00, (clone $bidTime)->modify('-8 days')->format('Y-m-d H:i:s')];
    $bids[] = [2, 9, 95000.00, (clone $bidTime)->modify('-5 days')->format('Y-m-d H:i:s')];

    // Item 10 (LV Trunk) - 2 bidders
    $bids[] = [1, 10, 8500.00, (clone $bidTime)->modify('-5 days')->format('Y-m-d H:i:s')];
    $bids[] = [4, 10, 9500.00, (clone $bidTime)->modify('-3 days')->format('Y-m-d H:i:s')];

    // Item 11 (Diamond) - 3 bidders
    $bids[] = [11, 1, 15500.00, (clone $bidTime)->modify('-2 days')->format('Y-m-d H:i:s')];
    $bids[] = [11, 3, 16500.00, (clone $bidTime)->modify('-1 day')->format('Y-m-d H:i:s')];
    $bids[] = [11, 4, 17200.00, (clone $bidTime)->modify('-10 hours')->format('Y-m-d H:i:s')];

    // Item 12 (Mustang) - 3 bidders
    $bids[] = [12, 3, 37000.00, (clone $bidTime)->modify('-8 days')->format('Y-m-d H:i:s')];
    $bids[] = [12, 1, 39000.00, (clone $bidTime)->modify('-6 days')->format('Y-m-d H:i:s')];
    $bids[] = [12, 3, 42000.00, (clone $bidTime)->modify('-4 days')->format('Y-m-d H:i:s')];

    // Item 13 (Gibson) - 2 bidders
    $bids[] = [13, 1, 13500.00, (clone $bidTime)->modify('-4 days')->format('Y-m-d H:i:s')];
    $bids[] = [13, 2, 14800.00, (clone $bidTime)->modify('-2 days')->format('Y-m-d H:i:s')];

    // Item 14 (Harry Potter) - 2 bidders
    $bids[] = [14, 2, 2800.00, (clone $bidTime)->modify('-5 days')->format('Y-m-d H:i:s')];
    $bids[] = [14, 1, 3100.00, (clone $bidTime)->modify('-3 days')->format('Y-m-d H:i:s')];

    // Item 15 (Jordan) - 3 bidders
    $bids[] = [15, 2, 8800.00, (clone $bidTime)->modify('-18 hours')->format('Y-m-d H:i:s')];
    $bids[] = [15, 4, 9400.00, (clone $bidTime)->modify('-10 hours')->format('Y-m-d H:i:s')];
    $bids[] = [15, 2, 9800.00, (clone $bidTime)->modify('-3 hours')->format('Y-m-d H:i:s')];

    // Completed item 16 (Canon R5) bids
    $bids[] = [16, 1, 2900.00, (clone $bidTime)->modify('-10 days')->format('Y-m-d H:i:s')];
    $bids[] = [16, 2, 3100.00, (clone $bidTime)->modify('-9 days')->format('Y-m-d H:i:s')];
    $bids[] = [16, 1, 3400.00, (clone $bidTime)->modify('-8 days')->format('Y-m-d H:i:s')];

    // Completed item 17 (iPad) bids
    $bids[] = [17, 2, 950.00, (clone $bidTime)->modify('-18 days')->format('Y-m-d H:i:s')];
    $bids[] = [17, 3, 1000.00, (clone $bidTime)->modify('-16 days')->format('Y-m-d H:i:s')];
    $bids[] = [17, 2, 1050.00, (clone $bidTime)->modify('-15 days')->format('Y-m-d H:i:s')];

    // Completed item 18 (Rolex Daytona) bids
    $bids[] = [18, 3, 29000.00, (clone $bidTime)->modify('-35 days')->format('Y-m-d H:i:s')];
    $bids[] = [18, 1, 30000.00, (clone $bidTime)->modify('-33 days')->format('Y-m-d H:i:s')];
    $bids[] = [18, 4, 30500.00, (clone $bidTime)->modify('-32 days')->format('Y-m-d H:i:s')];
    $bids[] = [18, 3, 31500.00, (clone $bidTime)->modify('-31 days')->format('Y-m-d H:i:s')];

    // Completed item 19 (Fender) bids
    $bids[] = [19, 1, 19000.00, (clone $bidTime)->modify('-18 days')->format('Y-m-d H:i:s')];
    $bids[] = [19, 4, 20500.00, (clone $bidTime)->modify('-16 days')->format('Y-m-d H:i:s')];
    $bids[] = [19, 1, 22000.00, (clone $bidTime)->modify('-15 days')->format('Y-m-d H:i:s')];

    // Expired item 20 (Comics) bids - below reserve
    $bids[] = [20, 2, 11000.00, (clone $bidTime)->modify('-10 days')->format('Y-m-d H:i:s')];
    $bids[] = [20, 3, 12500.00, (clone $bidTime)->modify('-8 days')->format('Y-m-d H:i:s')];

    // Expired item 21 (Wine) bids - below reserve
    $bids[] = [21, 3, 2200.00, (clone $bidTime)->modify('-18 days')->format('Y-m-d H:i:s')];
    $bids[] = [21, 4, 2400.00, (clone $bidTime)->modify('-16 days')->format('Y-m-d H:i:s')];

    $bidStmt = $pdo->prepare("
        INSERT INTO bids (item_id, bidder_id, amount, timestamp, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");

    $bidCount = 0;
    foreach ($bids as $i => $b) {
        try {
            $bidStmt->execute($b);
            $bidCount++;
        } catch (PDOException $e) {
            echo "  - ERROR on bid #{$i}: item={$b[0]} bidder={$b[1]} amount={$b[2]} - " . $e->getMessage() . "\n";
        }
    }
    echo "  - Created {$bidCount} bids\n";

    // ===================== ITEM IMAGES =====================
    echo "\nSeeding item images...\n";
    $imgStmt = $pdo->prepare("
        INSERT INTO item_images (item_id, image_url, thumbnail_url, upload_timestamp)
        VALUES (?, ?, ?, NOW())
    ");

    $imageMap = [
        1 => ['iphone15promax', 'apple-iphone'],
        2 => ['rolex-submariner', 'vintage-watch'],
        3 => ['banksy-art', 'street-art'],
        4 => ['hermes-birkin', 'luxury-bag'],
        5 => ['macbook-pro', 'apple-laptop'],
        6 => ['ps5-console', 'gaming-console'],
        7 => ['rtx-4090', 'graphics-card'],
        8 => ['samsung-tv', 'smart-tv'],
        9 => ['patek-nautilus', 'luxury-watch'],
        10 => ['louis-vuitton', 'vintage-trunk'],
        11 => ['diamond-necklace', 'fine-jewelry'],
        12 => ['ford-mustang', 'classic-car'],
        13 => ['gibson-lespaul', 'vintage-guitar'],
        14 => ['harry-potter', 'rare-book'],
        15 => ['air-jordan-1', 'sneakers'],
        16 => ['canon-eosr5', 'camera'],
        17 => ['ipad-pro', 'tablet'],
        18 => ['rolex-daytona', 'luxury-chronograph'],
        19 => ['fender-strat', 'electric-guitar'],
        20 => ['comic-books', 'comics-collection'],
        21 => ['vintage-wine', 'wine-collection'],
    ];

    $imgCount = 0;
    foreach ($imageMap as $itemId => $seeds) {
        for ($i = 0; $i < 3; $i++) {
            $imgStmt->execute([
                $itemId,
                "https://picsum.photos/seed/{$seeds[$i % 2]}{$i}/600/400",
                "https://picsum.photos/seed/{$seeds[$i % 2]}{$i}/150/150"
            ]);
            $imgCount++;
        }
    }
    echo "  - Created {$imgCount} images (3 per item)\n";

    // ===================== TRANSACTIONS (for completed items) =====================
    echo "\nSeeding transactions...\n";
    $transactions = [
        [16, 5, 1, 3400.00, 170.00, 3230.00, 'paid', 'shipped', 'completed', 'TRK001ABC', (clone $now)->modify('-7 days')->format('Y-m-d H:i:s')],
        [17, 6, 2, 1050.00, 52.50, 997.50, 'paid', 'delivered', 'completed', 'TRK002DEF', (clone $now)->modify('-13 days')->format('Y-m-d H:i:s')],
        [18, 7, 3, 31500.00, 1575.00, 29925.00, 'paid', 'pending', 'pending', NULL, (clone $now)->modify('-30 days')->format('Y-m-d H:i:s')],
        [19, 8, 1, 22000.00, 1100.00, 20900.00, 'paid', 'shipped', 'completed', 'TRK003GHI', (clone $now)->modify('-14 days')->format('Y-m-d H:i:s')],
    ];

    $txnStmt = $pdo->prepare("
        INSERT INTO transactions (item_id, seller_id, buyer_id, final_price, commission_amount, seller_payout, payment_status, shipping_status, payout_status, tracking_number, completed_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($transactions as $t) {
        $txnStmt->execute($t);
        echo "  - Transaction: item {$t[0]} buyer {$t[2]} -> seller {$t[1]} \${$t[3]}\n";
    }

    // txn IDs: 1-4

    // ===================== REVIEWS =====================
    echo "\nSeeding reviews...\n";
    $reviews = [
        [1, 1, 5, 5, 'Excellent seller! Item exactly as described. Fast shipping and great communication throughout.'],
        [1, 5, 1, 4, 'Great buyer. Quick payment and smooth transaction. Would sell to again.'],
        [2, 2, 6, 5, 'iPad arrived in perfect condition. Better than described. Very happy with purchase!'],
        [2, 6, 2, 5, 'Amazing buyer. Instant payment. A pleasure to deal with.'],
        [4, 1, 8, 4, 'Fender Strat is absolutely beautiful. Plays like a dream. Shipping was well-packaged.'],
        [4, 8, 1, 5, 'John is a fantastic buyer. Knowledgeable about guitars and pays quickly.'],
    ];

    $revStmt = $pdo->prepare("
        INSERT INTO reviews (transaction_id, reviewer_id, reviewee_id, rating, review_text, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    foreach ($reviews as $r) {
        $revStmt->execute($r);
        echo "  - Review: user {$r[1]} -> user {$r[2]} ({$r[3]} stars)\n";
    }

    // ===================== WATCHLIST =====================
    echo "\nSeeding watchlist...\n";
    $watchlist = [
        [1, 2], [1, 3], [1, 9], [1, 12],
        [2, 1], [2, 4], [2, 9], [2, 10],
        [3, 1], [3, 5], [3, 6], [3, 7],
        [4, 2], [4, 8], [4, 11], [4, 13],
    ];

    $wlStmt = $pdo->prepare("
        INSERT INTO watchlist (user_id, item_id, added_at) VALUES (?, ?, NOW())
    ");

    foreach ($watchlist as $w) {
        $wlStmt->execute($w);
    }
    echo "  - Created " . count($watchlist) . " watchlist entries\n";

    // ===================== NOTIFICATIONS =====================
    echo "\nSeeding notifications...\n";
    $notifications = [
        [1, 2, 'outbid', '{"message": "You have been outbid on Vintage Rolex Submariner 1968", "currentBid": 6200}'],
        [1, 6, 'auction_ending', '{"message": "Auction for Sony PlayStation 5 Pro Bundle ends in 1 hour!", "itemId": 6}'],
        [2, 1, 'outbid', '{"message": "You have been outbid on iPhone 15 Pro Max 256GB", "currentBid": 1150}'],
        [3, 3, 'bid_won', '{"message": "Congratulations! You won the auction for Rolex Daytona Ceramic!", "finalPrice": 31500}'],
        [1, 16, 'item_shipped', '{"message": "Canon EOS R5 Camera Body has been shipped! Tracking: TRK001ABC"}'],
        [2, 17, 'item_delivered', '{"message": "iPad Pro 12.9 has been delivered. Please leave a review!"}'],
        [5, 1, 'new_bid', '{"message": "John Buyer placed a bid of $1,150 on iPhone 15 Pro Max"}'],
        [5, 2, 'new_bid', '{"message": "Sarah Collector placed a bid of $6,200 on Vintage Rolex Submariner"}'],
        [6, 3, 'new_bid', '{"message": "Mike Bidder placed a bid of $2,100 on MacBook Pro"}'],
    ];

    $notStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, item_id, notification_type, payload, delivered, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");

    foreach ($notifications as $n) {
        $notStmt->execute($n);
    }
    echo "  - Created " . count($notifications) . " notifications\n";

    // ===================== MESSAGES =====================
    echo "\nSeeding messages...\n";
    $messages = [
        [1, 5, 1, 'Hi! Is the iPhone 15 Pro Max still available? Can you share more photos?', 1],
        [5, 1, 1, 'Yes, it is! I can send you additional photos tonight. Are you interested in bidding?', 1],
        [1, 5, 1, 'Definitely! Already placed a bid. Looking forward to the photos.', 1],
        [2, 7, 9, 'Hello, can you provide more details about the Patek Philippe? Is it available for viewing?', 1],
        [7, 2, 9, 'Of course! The watch is available for viewing at our showroom in NYC. Shall I schedule an appointment?', 0],
        [3, 8, 19, 'Is the Fender Strat all original? What year are the pickups?', 1],
        [8, 3, 19, 'Yes, all original 1963 parts. The pickups are original gray-bottoms. Happy to provide a detailed report.', 1],
        [3, 8, 19, 'That sounds great. Could you send the report to my email?', 0],
        [4, 5, 4, 'Is the Birkin bag authenticated? Do you have the original receipt?', 1],
        [5, 4, 4, 'Absolutely! It comes with full Hermes documentation, original receipt, and dust bag.', 1],
    ];

    $msgStmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, item_id, message, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    foreach ($messages as $m) {
        $msgStmt->execute($m);
    }
    echo "  - Created " . count($messages) . " messages\n";

    // ===================== PAYMENT METHODS =====================
    echo "\nSeeding payment methods...\n";
    $paymentMethods = [
        [1, 'card', '4242', 'Visa', 12, 2027, 1],
        [1, 'card', '8888', 'Mastercard', 6, 2026, 0],
        [2, 'card', '1234', 'Visa', 3, 2028, 1],
        [2, 'paypal', NULL, 'PayPal', NULL, NULL, 0],
        [3, 'card', '5678', 'Amex', 9, 2027, 1],
        [4, 'card', '9999', 'Visa', 1, 2027, 1],
    ];

    $pmStmt = $pdo->prepare("
        INSERT INTO payment_methods (user_id, type, last4, brand, expiry_month, expiry_year, is_default, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($paymentMethods as $pm) {
        $pmStmt->execute($pm);
        $label = $pm[2] ? "{$pm[3]} ending {$pm[2]}" : $pm[3];
        echo "  - User {$pm[0]}: {$label}\n";
    }

    // ===================== SHIPPING ADDRESSES =====================
    echo "\nSeeding shipping addresses...\n";
    $addresses = [
        [1, 'John Buyer', '123 Main Street', 'Apt 4B', 'New York', 'NY', '10001', 'US', '+1234567890', 'home', 1],
        [1, 'John Buyer', '456 Office Blvd', 'Suite 200', 'New York', 'NY', '10018', 'US', '+1234567890', 'work', 0],
        [2, 'Sarah Collector', '789 Palm Avenue', NULL, 'Los Angeles', 'CA', '90001', 'US', '+1987654321', 'home', 1],
        [3, 'Mike Bidder', '321 Oak Drive', NULL, 'Chicago', 'IL', '60601', 'US', '+1555123456', 'home', 1],
        [4, 'Emily Chen', '654 Maple Lane', 'Unit 5', 'San Francisco', 'CA', '94102', 'US', '+1444555666', 'home', 1],
    ];

    $addrStmt = $pdo->prepare("
        INSERT INTO shipping_addresses (user_id, full_name, address_line1, address_line2, city, state, zip_code, country, phone, address_type, is_default, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($addresses as $a) {
        $addrStmt->execute($a);
        echo "  - User {$a[0]}: {$a[1]}, {$a[4]}, {$a[5]}\n";
    }

    // ===================== ORDERS (for completed transactions) =====================
    echo "\nSeeding orders...\n";
    $orders = [
        [16, 1, 5, 1, 3400.00, 15.00, 'shipped', 'TRK001ABC', (clone $now)->modify('-6 days')->format('Y-m-d H:i:s'), NULL],
        [17, 2, 6, 3, 1050.00, 12.00, 'delivered', 'TRK002DEF', (clone $now)->modify('-12 days')->format('Y-m-d H:i:s'), (clone $now)->modify('-5 days')->format('Y-m-d H:i:s')],
        [19, 1, 8, 2, 22000.00, 25.00, 'shipped', 'TRK003GHI', (clone $now)->modify('-13 days')->format('Y-m-d H:i:s'), NULL],
    ];

    $ordStmt = $pdo->prepare("
        INSERT INTO orders (item_id, buyer_id, seller_id, shipping_address_id, total_amount, shipping_cost, status, tracking_number, shipped_at, delivered_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($orders as $o) {
        $ordStmt->execute($o);
        echo "  - Order: item {$o[0]} buyer {$o[1]} -> \${$o[4]} [{$o[6]}]\n";
    }

    // ===================== PAYOUTS =====================
    echo "\nSeeding payouts...\n";
    $payouts = [
        [5, 997.50, 'completed', 'bank_transfer', (clone $now)->modify('-10 days')->format('Y-m-d H:i:s'), (clone $now)->modify('-7 days')->format('Y-m-d H:i:s')],
        [5, 20900.00, 'pending', 'bank_transfer', (clone $now)->modify('-2 days')->format('Y-m-d H:i:s'), NULL],
        [6, 997.50, 'completed', 'paypal', (clone $now)->modify('-10 days')->format('Y-m-d H:i:s'), (clone $now)->modify('-8 days')->format('Y-m-d H:i:s')],
        [7, 29925.00, 'pending', 'bank_transfer', (clone $now)->modify('-1 day')->format('Y-m-d H:i:s'), NULL],
    ];

    $payStmt = $pdo->prepare("
        INSERT INTO payouts (seller_id, amount, status, payment_method, requested_at, processed_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($payouts as $p) {
        $payStmt->execute($p);
        echo "  - Seller {$p[0]}: \${$p[1]} [{$p[2]}]\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SEEDING COMPLETE!\n";
    echo str_repeat("=", 50) . "\n\n";

    echo "TEST ACCOUNTS:\n";
    echo "  Buyer:   buyer@bidorbit.com   / buyer123\n";
    echo "  Buyer:   sarah@bidorbit.com   / sarah123\n";
    echo "  Buyer:   mike@bidorbit.com    / mike123\n";
    echo "  Buyer:   emily@bidorbit.com   / emily123\n";
    echo "  Seller:  seller@bidorbit.com  / seller123\n";
    echo "  Seller:  techdeals@bidorbit.com / techdeals123\n";
    echo "  Seller:  luxury@bidorbit.com  / luxury123\n";
    echo "  Seller:  vintage@bidorbit.com / vintage123\n";
    echo "  Admin:   admin@bidorbit.com   / admin123\n";
    echo "  Mod:     mod@bidorbit.com     / mod123\n\n";

    echo "DATA SUMMARY:\n";
    echo "  Users:         10 (4 buyers, 4 sellers, 1 admin, 1 moderator)\n";
    echo "  Items:         21 (14 active, 4 completed, 2 expired)\n";
    echo "  Bids:          " . count($bids) . "\n";
    echo "  Images:        {$imgCount}\n";
    echo "  Transactions:  4\n";
    echo "  Reviews:       6\n";
    echo "  Watchlist:     " . count($watchlist) . "\n";
    echo "  Notifications: " . count($notifications) . "\n";
    echo "  Messages:      " . count($messages) . "\n";
    echo "  Payment Methods: " . count($paymentMethods) . "\n";
    echo "  Addresses:     " . count($addresses) . "\n";
    echo "  Orders:        " . count($orders) . "\n";
    echo "  Payouts:       " . count($payouts) . "\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
