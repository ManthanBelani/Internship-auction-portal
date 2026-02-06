<?php
// Setup SQLite database for development

echo "Setting up SQLite database...\n\n";

$dbPath = __DIR__ . '/database/auction_portal.sqlite';
$dbDir = dirname($dbPath);

// Create database directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "✓ Created database directory\n";
}

// Create SQLite database
try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ SQLite database created: {$dbPath}\n\n";
    
    // Read and execute migrations
    $migrationsDir = __DIR__ . '/database/migrations';
    $migrations = glob($migrationsDir . '/*.sql');
    sort($migrations);
    
    echo "Running migrations...\n";
    foreach ($migrations as $migration) {
        $filename = basename($migration);
        echo "  • {$filename}... ";
        
        $sql = file_get_contents($migration);
        
        // Convert MySQL syntax to SQLite
        $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
        $sql = str_replace('ENGINE=InnoDB', '', $sql);
        $sql = preg_replace('/INT\(\d+\)/', 'INTEGER', $sql);
        $sql = preg_replace('/VARCHAR\(\d+\)/', 'TEXT', $sql);
        $sql = str_replace('DATETIME', 'TEXT', $sql);
        $sql = str_replace('DECIMAL(10,2)', 'REAL', $sql);
        $sql = str_replace('ENUM', 'TEXT CHECK', $sql);
        
        try {
            $pdo->exec($sql);
            echo "✓\n";
        } catch (PDOException $e) {
            echo "✗ (might already exist)\n";
        }
    }
    
    echo "\n✓ SQLite setup complete!\n";
    echo "\nUpdate your .env file:\n";
    echo "DB_CONNECTION=sqlite\n";
    echo "DB_DATABASE={$dbPath}\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
