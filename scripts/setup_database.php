<?php
/**
 * Database Setup Script
 * Author: Ali Salem <admin@alisalem.me>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    // Create database directory if it doesn't exist
    if (!is_dir(__DIR__)) {
        mkdir(__DIR__, 0755, true);
    }

    // Initialize database connection
    $pdo = Database::getInstance()->getConnection();

    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create favorites table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            article_id TEXT NOT NULL,
            title TEXT NOT NULL,
            url TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE(user_id, article_id)
        )
    ");

    // Create rate_limits table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            token TEXT,
            request_count INTEGER DEFAULT 1,
            first_request_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_request_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    echo "Database setup completed successfully!\n";
} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
} 