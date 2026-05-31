<?php
// =============================================================
// VantageMarket — Database Singleton Connection Class
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Config;

use PDO;
use PDOException;

/**
 * Singleton database wrapper class.
 * Ensures only one connection instance to the database is created and shared.
 */
final class Database
{
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation from external code.
     */
    private function __construct()
    {
    }

    /**
     * Private clone method to prevent cloning of the singleton instance.
     */
    private function __clone()
    {
    }

    /**
     * Private wakeup method to prevent unserializing of the singleton instance.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Get the single PDO database instance.
     * Implements lazy initialization to connect only when requested.
     *
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // If access is denied, try with empty password (common for default local XAMPP/WAMP setups)
                if (($e->getCode() === 1045 || str_contains($e->getMessage(), '1045')) && $config['password'] !== '') {
                    try {
                        self::$instance = new PDO(
                            $dsn,
                            $config['username'],
                            '',
                            $config['options']
                        );
                        return self::$instance;
                    } catch (PDOException $e2) {
                        // ignore and fall through to throw original exception
                    }
                }
                // Catch and throw connection error securely
                throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}
