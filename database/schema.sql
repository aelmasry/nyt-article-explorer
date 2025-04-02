-- Start by creating the users table
CREATE TABLE
    IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- User tokens table
CREATE TABLE
    IF NOT EXISTS user_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- Table for favorite articles
CREATE TABLE
    IF NOT EXISTS favorite_articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        article_id TEXT NOT NULL,
        title TEXT NOT NULL,
        author TEXT,
        published_date TEXT,
        url TEXT NOT NULL,
        thumbnail_url TEXT,
        snippet TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        UNIQUE (user_id, article_id)
    );

-- Table to temporarily store NYT API responses
CREATE TABLE
    IF NOT EXISTS api_cache (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        request_hash TEXT NOT NULL UNIQUE,
        response_data TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL
    );

-- Table to track request rates (Rate Limiting)
CREATE TABLE
    IF NOT EXISTS rate_limits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        identifier TEXT NOT NULL, -- Either IP or user_id
        request_count INTEGER DEFAULT 1,
        window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (identifier)
    );

-- Logs table
CREATE TABLE
    IF NOT EXISTS logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        level TEXT NOT NULL,
        message TEXT NOT NULL,
        context TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Insert some users for testing
INSERT INTO
    users (username, email, password)
VALUES
    (
        'admin',
        'admin@example.com',
        '$2y$10$BQBFICOKnK0UHvRrJdH2H.SQODPuneCJNmSRWnfAax6ZUWXxpb5zy'
    );

-- Password: admin123