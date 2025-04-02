<?php
/**
 * Authentication Controller
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Controllers;

use App\Services\JwtService;
use App\Config\Database;
use PDO;
use PDOException;

class AuthController
{
    private $jwtService;
    private $db;

    public function __construct()
    {
        $this->jwtService = new JwtService();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Register new user
     */
    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters long']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT)
            ]);

            $userId = $this->db->lastInsertId();
            $token = $this->jwtService->generateToken([
                'user_id' => $userId,
                'email' => $data['email'],
                'username' => $data['username']
            ]);

            echo json_encode([
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'email' => $data['email'],
                    'username' => $data['username']
                ]
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // SQLITE_CONSTRAINT_UNIQUE
                http_response_code(409);
                echo json_encode(['error' => 'Email or username already exists']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to register user']);
            }
        }
    }

    /**
     * Login user
     */
    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT id, email, password, username 
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        $token = $this->jwtService->generateToken([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username']
        ]);

        echo json_encode([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username']
            ]
        ]);
    }
} 