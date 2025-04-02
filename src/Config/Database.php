<?php
/**
 * Database Configuration
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $dbPath = $_ENV['DB_PATH'];
            $this->connection = new PDO(
                "sqlite:{$dbPath}",
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Create cache table if it doesn't exist
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS cache (
                    key TEXT PRIMARY KEY,
                    data TEXT NOT NULL,
                    expires_at DATETIME NOT NULL
                )
            ");
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get database connection instance
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}