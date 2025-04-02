<?php
/**
 * NYT API Service
 * Author: Ali Salem <admin@alisalem.me>
 */

namespace App\Services;

use App\Config\Database;
use PDO;

class NytApiService
{
    private $apiKey;
    private $baseUrl;
    private $cacheDuration;
    private $db;

    public function __construct()
    {
        $this->apiKey = $_ENV['NYT_API_KEY'];
        $this->baseUrl = $_ENV['NYT_API_BASE_URL'];
        $this->cacheDuration = $_ENV['CACHE_DURATION'];
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Search for articles
     * @param string $query Search query
     * @param int $page Page number (0-based)
     * @return array Search results
     */
    public function searchArticles(string $query, int $page = 0): array
    {
        $cacheKey = "search_{$query}_{$page}";
        $cachedResult = $this->getFromCache($cacheKey);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $url = $this->buildSearchUrl($query, $page);
        $response = $this->makeRequest($url);

        if ($response) {
            $this->saveToCache($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Get article details by URL
     * @param string $url Article URL
     * @return array|null Article details
     */
    public function getArticleDetails(string $url): ?array
    {
        $cacheKey = "article_" . md5($url);
        $cachedResult = $this->getFromCache($cacheKey);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Since NYT API doesn't provide direct article details endpoint,
        // we'll need to parse the article URL to get the article ID
        $articleId = $this->extractArticleId($url);
        if (!$articleId) {
            return null;
        }

        $url = "https://api.nytimes.com/svc/search/v2/articlesearch.json?fq=web_url:(\"{$url}\")&api-key={$this->apiKey}";
        $response = $this->makeRequest($url);

        if ($response && isset($response['response']['docs'][0])) {
            $article = $response['response']['docs'][0];
            $this->saveToCache($cacheKey, $article);
            return $article;
        }

        return null;
    }

    /**
     * Build search URL with parameters
     * @param string $query Search query
     * @param int $page Page number
     * @return string Complete URL
     */
    private function buildSearchUrl(string $query, int $page): string
    {
        $params = [
            'q' => urlencode($query),
            'page' => $page,
            'api-key' => $this->apiKey,
            'sort' => 'newest'
        ];

        return $this->baseUrl . '?' . http_build_query($params);
    }

    /**
     * Make HTTP request to NYT API
     * @param string $url API URL
     * @return array|null Response data
     */
    private function makeRequest(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }

        return null;
    }

    /**
     * Extract article ID from URL
     * @param string $url Article URL
     * @return string|null Article ID
     */
    private function extractArticleId(string $url): ?string
    {
        if (preg_match('/\/(\d{4}\/\d{2}\/\d{2}\/[^\/]+)$/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get data from cache
     * @param string $key Cache key
     * @return array|null Cached data
     */
    private function getFromCache(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT data FROM cache WHERE key = ? AND expires_at > datetime('now')");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? json_decode($result['data'], true) : null;
    }

    /**
     * Save data to cache
     * @param string $key Cache key
     * @param array $data Data to cache
     */
    private function saveToCache(string $key, array $data): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->cacheDuration);
        
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO cache (key, data, expires_at)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $key,
            json_encode($data),
            $expiresAt
        ]);
    }
} 