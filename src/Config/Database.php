<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                // Check if using SQLite or MySQL
                $dbConnection = $_ENV['DB_CONNECTION'] ?? 'mysql';
                
                if ($dbConnection === 'sqlite') {
                    // SQLite connection
                    $dbPath = $_ENV['DB_DATABASE'] ?? __DIR__ . '/../../database/auction_portal.sqlite';
                    
                    // Convert relative path to absolute
                    if (!file_exists($dbPath)) {
                        $dbPath = __DIR__ . '/../../' . $dbPath;
                    }
                    
                    // Ensure directory exists
                    $dbDir = dirname($dbPath);
                    if (!is_dir($dbDir)) {
                        mkdir($dbDir, 0755, true);
                    }
                    
                    $dsn = "sqlite:{$dbPath}";
                    error_log("Using SQLite Database at: " . $dbPath);
                    
                    self::$connection = new PDO($dsn, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } else {
                    // MySQL connection
                    $host = $_ENV['DB_HOST'] ?? 'localhost';
                    $port = $_ENV['DB_PORT'] ?? '3306';
                    $dbname = $_ENV['DB_NAME'] ?? 'auction_portal';
                    $username = $_ENV['DB_USER'] ?? 'root';
                    $password = $_ENV['DB_PASSWORD'] ?? '';

                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                    
                    self::$connection = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                }
            } catch (PDOException $e) {
                throw new \RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function closeConnection(): void
    {
        self::$connection = null;
    }
}
