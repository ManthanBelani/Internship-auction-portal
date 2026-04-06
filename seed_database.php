<?php
/**
 * Simple Database Seeder Script for BidOrbit Auction Portal
 * 
 * This script populates the SQLite database with sample data for testing.
 * Run with: php seed_database.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== BidOrbit Database Seeder ===\n\n";

// Database path
$dbPath = __DIR__ . '/database/auction_portal.sqlite';

try {
    // Connect to SQLite database
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n\n";
    
    // Delete existing data in correct order (respecting foreign keys)
    echo "Clearing existing data...\n";
    
    // Delete in correct order (child tables first)
    $pdo->exec('DELETE FROM bids');
    echo "  - Deleted all bids\n";
    
    $pdo->exec('DELETE FROM item_images');
    echo "  - Deleted all item images\n";
    
    $pdo->exec('DELETE FROM items');
    echo "  - Deleted all items\n";
    
    $pdo->exec('DELETE FROM users');
    echo "  - Deleted all users\n";
    
    // Sample users data (matching the actual schema: name, email, password, phone, role, status)
    $users = [
        [
            'John Buyer',
            'buyer@bidorbit.com',
            password_hash('buyer123', PASSWORD_DEFAULT),
            '+1234567890',
            'buyer',
            'active',
        ],
        [
            'Jane Seller',
            'seller@bidorbit.com',
            password_hash('seller123', PASSWORD_DEFAULT),
            '+1987654321',
            'seller',
            'active',
        ],
        [
            'Admin User',
            'admin@bidorbit.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            '+1555051234',
            'admin',
            'active',
        ],
    ];
    
    echo "\nSeeding users...\n";
    $userStmt = $pdo->prepare("
        INSERT INTO users (name, email, password, phone, role, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    ");
    
    foreach ($users as $userData) {
        $userStmt->execute($userData);
        echo "  - Created user: {$userData[1]} ({$userData[4]})\n";
    }
    
    // Sample items/auctions data
    // Schema: seller_id, title, description, category, starting_price, current_price, reserve_price, highest_bidder_id, reserve_met, commission_rate, end_time, status
    $items = [
        [
            2, // seller_id (Jane Seller)
            'iPhone 15 Pro Max - 256GB',
            'Brand new iPhone 15 Pro Max with 256GB storage. Factory unlocked, includes all original accessories. Perfect condition, still in sealed box.',
            'Electronics',
            999.99,
            1150.00,
            null, // reserve_price
            1, // highest_bidder_id (John Buyer)
            1, // reserve_met
            5.0, // commission_rate
            '2024-12-31 23:59:59',
            'active',
        ],
        [
            2, // seller_id
            'Vintage Rolex Submariner Watch',
            'Authentic 1960s Rolex Submariner in excellent condition. Recently serviced with all papers. Serial number intact. A true collectors item.',
            'Jewelry',
            5000.00,
            5300.00,
            4500.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-06-15 20:00:00',
            'active',
        ],
        [
            2, // seller_id
            'MacBook Pro 16" M3 Max',
            'Like new MacBook Pro with M3 Max chip, 32GB RAM, 1TB SSD. AppleCare+ included until 2026. Original box and accessories.',
            'Electronics',
            1899.00,
            2100.00,
            null, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-08-01 22:00:00',
            'active',
        ],
        [
            2, // seller_id
            'Original Picasso Sketch',
            'Authenticated preliminary sketch by Pablo Picasso from his Blue Period. Certificate of authenticity included.',
            'Art',
            50000.00,
            56000.00,
            40000.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-04-20 18:00:00',
            'active',
        ],
        [
            2, // seller_id
            'Nike Air Jordan 1 Original (1985)',
            'Deadstock pair of original 1985 Air Jordan 1 in size 10. Never worn, original box included.',
            'Fashion',
            8000.00,
            8500.00,
            6000.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-03-10 14:00:00',
            'active',
        ],
        [
            2, // seller_id
            '1967 Ford Mustang Fastback',
            'Fully restored 1967 Ford Mustang Fastback. 281 V8 engine, manual transmission. Show winner.',
            'Vehicles',
            35000.00,
            36000.00,
            30000.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-02-25 17:00:00',
            'active',
        ],
        [
            2, // seller_id
            'Hermes Birkin Bag 25',
            'Authentic Hermes Birkin 25 in Togo leather with gold hardware. Store fresh, never carried.',
            'Fashion',
            12000.00,
            12500.00,
            10000.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-03-05 19:00:00',
            'active',
        ],
        [
            2, // seller_id
            'First Edition Harry Potter Book',
            'UK First Edition of Harry Potter and the Philosophers Stone by J.K. Rowling. Excellent condition.',
            'Books',
            2500.00,
            2700.00,
            2000.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-03-15 21:00:00',
            'active',
        ],
        [
            2, // seller_id
            'Sony PlayStation 5 Pro',
            'Brand new PS5 Pro console with DualSense controller. Includes 2 games: Spider-Man 2 and God of War Ragnarok. Sealed in box.',
            'Electronics',
            699.99,
            750.00,
            null, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-04-30 22:00:00',
            'active',
        ],
        [
            2, // seller_id
            'Vintage Gibson Les Paul Guitar',
            '1960 Gibson Les Paul Standard in Cherry Sunburst. All original parts, plays beautifully. Comes with original hard case.',
            'Music',
            15000.00,
            16500.00,
            12000.00, // reserve_price
            1, // highest_bidder_id
            1, // reserve_met
            5.0, // commission_rate
            '2024-05-15 20:00:00',
            'active',
        ],
    ];
    
    echo "\nSeeding items/auctions...\n";
    $itemStmt = $pdo->prepare("
        INSERT INTO items (seller_id, title, description, category, starting_price, current_price, reserve_price, highest_bidder_id, reserve_met, commission_rate, end_time, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    ");
    
    foreach ($items as $item) {
        try {
            $itemStmt->execute($item);
            echo "  - Created item: {$item[1]}\n";
        } catch (PDOException $e) {
            echo "  - Error creating item {$item[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    // Sample bids - now with proper unique combinations
    $bids = [
        [1, 1, 1050.00],  // buyer bids on iPhone
        [1, 1, 1100.00],
        [1, 1, 1150.00],
        [1, 2, 5200.00],  // buyer bids on Rolex
        [1, 2, 5250.00],
        [1, 2, 5300.00],
        [1, 3, 1950.00],  // buyer bids on MacBook
        [1, 3, 2000.00],
        [1, 3, 2100.00],
        [1, 4, 55000.00], // buyer bids on Picasso
        [1, 4, 56000.00],
        [1, 5, 8200.00],  // buyer bids on Jordan
        [1, 5, 8500.00],
        [1, 6, 35500.00], // buyer bids on Mustang
        [1, 6, 36000.00],
        [1, 7, 12300.00], // buyer bids on Birkin
        [1, 7, 12500.00],
        [1, 8, 2600.00],  // buyer bids on Harry Potter
        [1, 8, 2700.00],
        [1, 9, 720.00],   // buyer bids on PS5
        [1, 9, 750.00],
        [1, 10, 15500.00], // buyer bids on Guitar
        [1, 10, 16000.00],
        [1, 10, 16500.00],
    ];
    
    echo "\nSeeding bids...\n";
    $bidStmt = $pdo->prepare("
        INSERT INTO bids (user_id, item_id, amount, created_at) 
        VALUES (?, ?, ?, datetime('now'))
    ");
    
    $bidCount = 0;
    foreach ($bids as $bid) {
        try {
            $bidStmt->execute($bid);
            $bidCount++;
        } catch (PDOException $e) {
            // Skip if bid already exists or other error
        }
    }
    echo "  - Created $bidCount bids\n";
    
    // Add sample item images
    echo "\nSeeding item images...\n";
    $imageStmt = $pdo->prepare("
        INSERT INTO item_images (item_id, image_path, thumbnail_path, is_primary, created_at) 
        VALUES (?, ?, ?, ?, datetime('now'))
    ");
    
    // Add placeholder images for each item
    for ($i = 1; $i <= count($items); $i++) {
        $imageStmt->execute([
            $i, 
            'https://picsum.photos/400/300?random=' . $i, 
            'https://picsum.photos/100/100?random=' . $i,
            1 // is_primary
        ]);
    }
    echo "  - Created images for " . count($items) . " items\n";
    
    echo "\n=== Seeding Complete! ===\n";
    echo "\nTest Accounts:\n";
    echo "  Buyer:  buyer@bidorbit.com  / buyer123\n";
    echo "  Seller: seller@bidorbit.com / seller123\n";
    echo "  Admin:  admin@bidorbit.com  / admin123\n";
    echo "\nSample Auction Items:\n";
    echo "  - iPhone 15 Pro Max - Starting at \$999.99\n";
    echo "  - Vintage Rolex Submariner - Starting at \$5,000\n";
    echo "  - MacBook Pro 16\" M3 Max - Starting at \$1,899\n";
    echo "  - Original Picasso Sketch - Starting at \$50,000\n";
    echo "  - Nike Air Jordan 1 (1985) - Starting at \$8,000\n";
    echo "  - 1967 Ford Mustang Fastback - Starting at \$35,000\n";
    echo "  - Hermes Birkin Bag 25 - Starting at \$12,000\n";
    echo "  - First Edition Harry Potter - Starting at \$2,500\n";
    echo "  - Sony PlayStation 5 Pro - Starting at \$699.99\n";
    echo "  - Vintage Gibson Les Paul - Starting at \$15,000\n";
    echo "\nYou can now test the application with these credentials!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
