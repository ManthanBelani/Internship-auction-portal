<?php

namespace App\Utils;

use App\Config\Database;
use PDO;

class Transaction
{
    private static ?PDO $db = null;
    private static int $transactionLevel = 0;

    /**
     * Execute a callback within a database transaction
     * 
     * @param callable $callback Function to execute
     * @return mixed Return value from callback
     * @throws \Exception If transaction fails
     */
    public static function run(callable $callback)
    {
        self::$db = Database::getConnection();
        
        try {
            self::begin();
            
            $result = $callback();
            
            self::commit();
            
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Begin a transaction
     */
    public static function begin(): void
    {
        if (self::$db === null) {
            self::$db = Database::getConnection();
        }

        if (self::$transactionLevel === 0) {
            self::$db->beginTransaction();
        } else {
            // Nested transaction using savepoints
            self::$db->exec("SAVEPOINT LEVEL" . self::$transactionLevel);
        }

        self::$transactionLevel++;
    }

    /**
     * Commit a transaction
     */
    public static function commit(): void
    {
        if (self::$transactionLevel === 0) {
            throw new \RuntimeException('No transaction to commit');
        }

        self::$transactionLevel--;

        if (self::$transactionLevel === 0) {
            self::$db->commit();
        } else {
            // Release savepoint
            self::$db->exec("RELEASE SAVEPOINT LEVEL" . self::$transactionLevel);
        }
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(): void
    {
        if (self::$transactionLevel === 0) {
            return; // No transaction to rollback
        }

        self::$transactionLevel--;

        if (self::$transactionLevel === 0) {
            if (self::$db->inTransaction()) {
                self::$db->rollBack();
            }
        } else {
            // Rollback to savepoint
            self::$db->exec("ROLLBACK TO SAVEPOINT LEVEL" . self::$transactionLevel);
        }
    }

    /**
     * Check if currently in a transaction
     */
    public static function inTransaction(): bool
    {
        return self::$transactionLevel > 0;
    }

    /**
     * Get current transaction level
     */
    public static function getLevel(): int
    {
        return self::$transactionLevel;
    }
}
