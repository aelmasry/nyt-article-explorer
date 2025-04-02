<?php

namespace App\Repositories;

use App\Config\Database;
use App\Utils\Logger;
use PDO;

class ArticleRepository {
    private PDO $db;
    private Logger $logger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Add an article to user's favorites
     */
    public function addFavorite(int $userId, array $article): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO favorite_articles (
                    user_id, article_id, title, author, published_date, 
                    url, thumbnail_url, snippet
                ) 
                VALUES (
                    :user_id, :article_id, :title, :author, :published_date,
                    :url, :thumbnail_url, :snippet
                )
                ON CONFLICT(user_id, article_id) DO NOTHING
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':article_id' => $article['id'] ?? md5($article['url']),
                ':title' => $article['title'] ?? '',
                ':author' => $article['author'] ?? '',
                ':published_date' => $article['published_date'] ?? '',
                ':url' => $article['url'] ?? '',
                ':thumbnail_url' => $article['thumbnail_url'] ?? '',
                ':snippet' => $article['snippet'] ?? ''
            ]);
            
            $this->logger->info('Article added to favorites', [
                'user_id' => $userId,
                'article_id' => $article['id'] ?? md5($article['url'])
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to add article to favorites', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Remove an article from user's favorites
     */
    public function removeFavorite(int $userId, string $articleId): bool {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM favorite_articles 
                WHERE user_id = :user_id AND article_id = :article_id
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':article_id' => $articleId
            ]);
            
            $this->logger->info('Article removed from favorites', [
                'user_id' => $userId,
                'article_id' => $articleId
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to remove article from favorites', [
                'user_id' => $userId,
                'article_id' => $articleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get user's favorite articles
     */
    public function getFavorites(int $userId, int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->db->prepare("
                SELECT * FROM favorite_articles 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $favorites = $stmt->fetchAll();
            
            // Get total count for pagination
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM favorite_articles 
                WHERE user_id = :user_id
            ");
            
            $countStmt->execute([':user_id' => $userId]);
            $total = $countStmt->fetch()['total'];
            
            return [
                'data' => $favorites,
                'pagination' => [
                    'total' => (int)$total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get favorite articles', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => 0
                ]
            ];
        }
    }
    
    /**
     * Check if an article is in user's favorites
     */
    public function isFavorite(int $userId, string $articleId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM favorite_articles 
                WHERE user_id = :user_id AND article_id = :article_id
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':article_id' => $articleId
            ]);
            
            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to check if article is favorite', [
                'user_id' => $userId,
                'article_id' => $articleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}