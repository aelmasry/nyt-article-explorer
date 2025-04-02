<?php

namespace App\Services;

use App\Config\Database;
use App\Utils\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use Exception;

class AuthService {
    private PDO $db;
    private Logger $logger;
    private string $jwtSecret;
    private int $jwtExpiry;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_secret_key_for_development';
        $this->jwtExpiry = (int)($_ENV['JWT_EXPIRY'] ?? 86400); // Default: 24 hours
    }
    
    /**
     * Authenticate a user and generate JWT token
     */
    public function login(string $username, string $password): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, email, password 
            FROM users 
            WHERE username = :username
        ");
        
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->logger->warning('Login failed: Invalid credentials', ['username' => $username]);
            return null;
        }
        
        // Generate JWT token
        $token = $this->generateToken($user['id']);
        
        // Store token in database
        $this->storeToken($user['id'], $token);
        
        $this->logger->info('Login successful', ['user_id' => $user['id']]);
        
        return [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ],
            'token' => $token
        ];
    }
    
    /**
     * Register a new user
     */
    public function register(string $username, string $email, string $password): ?array {
        // Check if username or email already exists
        $stmt = $this->db->prepare("
            SELECT id FROM users WHERE username = :username OR email = :email
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);
        
        if ($stmt->fetch()) {
            $this->logger->warning('Registration failed: Username or email already exists', [
                'username' => $username,
                'email' => $email
            ]);
            return null;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password) 
            VALUES (:username, :email, :password)
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // Generate JWT token
        $token = $this->generateToken($userId);
        
        // Store token in database
        $this->storeToken($userId, $token);
        
        $this->logger->info('User registered successfully', ['user_id' => $userId]);
        
        return [
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email
            ],
            'token' => $token
        ];
    }
    
    /**
     * Validate JWT token
     */
    public function validateToken(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Check if token exists in database
            $stmt = $this->db->prepare("
                SELECT * FROM user_tokens 
                WHERE user_id = :user_id AND token = :token AND expires_at > CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                ':user_id' => $decoded->user_id,
                ':token' => $token
            ]);
            
            if (!$stmt->fetch()) {
                $this->logger->warning('Token validation failed: Token not found in database', [
                    'user_id' => $decoded->user_id
                ]);
                return null;
            }
            
            return [
                'user_id' => $decoded->user_id,
                'exp' => $decoded->exp
            ];
        } catch (Exception $e) {
            $this->logger->warning('Token validation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Logout user by invalidating token
     */
    public function logout(string $token): bool {
        $stmt = $this->db->prepare("
            DELETE FROM user_tokens WHERE token = :token
        ");
        
        $stmt->execute([':token' => $token]);
        
        $this->logger->info('User logged out', ['affected_rows' => $stmt->rowCount()]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Generate JWT token
     */
    private function generateToken(int $userId): string {
        $issuedAt = time();
        $expiry = $issuedAt + $this->jwtExpiry;
        
        $payload = [
            'user_id' => $userId,
            'iat' => $issuedAt,
            'exp' => $expiry
        ];
        
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
    
    /**
     * Store token in database
     */
    private function storeToken(int $userId, string $token): void {
        // First, remove any expired tokens for this user
        $stmt = $this->db->prepare("
            DELETE FROM user_tokens 
            WHERE user_id = :user_id AND expires_at <= CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([':user_id' => $userId]);
        
        // Insert new token
        $stmt = $this->db->prepare("
            INSERT INTO user_tokens (user_id, token, expires_at) 
            VALUES (:user_id, :token, datetime('now', '+' || :expires || ' seconds'))
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires' => $this->jwtExpiry
        ]);
    }
}