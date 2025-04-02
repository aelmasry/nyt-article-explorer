<?php
/**
 * API Controller
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Controllers;

use App\Services\NytApiService;
use App\Services\JwtService;
use App\Config\Database;
use PDO;
use PDOException;

class ApiController
{
    private $nytService;
    private $jwtService;
    private $db;

    public function __construct()
    {
        $this->nytService = new NytApiService();
        $this->jwtService = new JwtService();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Search articles
     */
    public function searchArticles(): void
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 0);

        if (empty($query)) {
            http_response_code(400);
            echo json_encode(['error' => 'Search query is required']);
            return;
        }

        $results = $this->nytService->searchArticles($query, $page);
        echo json_encode($results);
    }

    /**
     * Get article details
     */
    public function getArticleDetails(): void
    {
        $url = $_GET['url'] ?? '';

        if (empty($url)) {
            http_response_code(400);
            echo json_encode(['error' => 'Article URL is required']);
            return;
        }

        $article = $this->nytService->getArticleDetails($url);
        
        if (!$article) {
            http_response_code(404);
            echo json_encode(['error' => 'Article not found']);
            return;
        }

        echo json_encode($article);
    }

    /**
     * Add article to favorites
     */
    public function addFavorite(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['article_id']) || !isset($data['title']) || !isset($data['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO favorites (user_id, article_id, title, url)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $data['article_id'],
                $data['title'],
                $data['url']
            ]);
            echo json_encode(['message' => 'Article added to favorites']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // SQLITE_CONSTRAINT_UNIQUE
                http_response_code(409);
                echo json_encode(['error' => 'Article already in favorites']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add article to favorites']);
            }
        }
    }

    /**
     * Remove article from favorites
     */
    public function removeFavorite(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $articleId = $_GET['article_id'] ?? '';
        if (empty($articleId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Article ID is required']);
            return;
        }

        $stmt = $this->db->prepare("
            DELETE FROM favorites 
            WHERE user_id = ? AND article_id = ?
        ");
        $stmt->execute([$userId, $articleId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Article removed from favorites']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Article not found in favorites']);
        }
    }

    /**
     * Get user's favorite articles
     */
    public function getFavorites(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM favorites 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($favorites);
    }

    /**
     * Get current user ID from JWT token
     * @return int|null User ID or null if not authenticated
     */
    private function getCurrentUserId(): ?int
    {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        if (empty($token) || !preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            return null;
        }

        $token = $matches[1];
        $payload = $this->jwtService->validateToken($token);
        
        return $payload ? $payload['user_id'] : null;
    }
}