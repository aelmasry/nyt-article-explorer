<?php

namespace App\Services;

use App\Config\Database;
use App\Utils\Logger;
use PDO;

/**
 * Service class for interacting with the New York Times API
 * 
 * This service handles all API interactions with the NYT API, including
 * article searches and detail retrieval. It implements a robust caching
 * mechanism to optimize API usage and improve response times.
 */
class NYTApiService
{
    private string $apiKey;
    private string $baseUrl;
    private Logger $logger;
    private PDO $db;
    private int $cacheDuration;

    /**
     * Initialize the NYT API service with configuration from environment
     */
    public function __construct()
    {
        $this->apiKey = $_ENV['NYT_API_KEY'] ?? '';
        $this->baseUrl = $_ENV['NYT_API_BASE_URL'] ?? 'https://api.nytimes.com/svc/search/v2/articlesearch.json';
        $this->logger = new Logger();
        $this->db = Database::getInstance()->getConnection();
        $this->cacheDuration = (int)($_ENV['CACHE_DURATION'] ?? 3600);
    }

    /**
     * Search for articles using the NYT API
     * 
     * @param string $query The search query
     * @param int $page The page number for pagination
     * @return array Response containing article search results
     */
    public function searchArticles(string $query, int $page = 0): array
    {
        $params = [
            'q' => $query,
            'page' => $page,
            'api-key' => $this->apiKey
        ];

        $cacheKey = $this->generateCacheKey('search', $params);
        $cachedResponse = $this->getCachedResponse($cacheKey);

        if ($cachedResponse) {
            $this->logger->info('Retrieved cached NYT API response', ['query' => $query, 'page' => $page]);
            return $cachedResponse;
        }

        $url = $this->buildUrl($params);
        $response = $this->makeRequest($url);
        $this->cacheResponse($cacheKey, $response);

        return $response;
    }

    /**
     * Retrieve detailed information for a specific article
     * 
     * @param string $articleUrl The URL of the article to retrieve
     * @return array Response containing article details
     */
    public function getArticleDetails(string $articleUrl): array
    {
        $params = [
            'fq' => 'web_url:"' . $articleUrl . '"',
            'api-key' => $this->apiKey
        ];

        $cacheKey = $this->generateCacheKey('details', $params);
        $cachedResponse = $this->getCachedResponse($cacheKey);

        if ($cachedResponse) {
            $this->logger->info('Retrieved cached article details', ['url' => $articleUrl]);
            return $cachedResponse;
        }

        $url = $this->buildUrl($params);
        $response = $this->makeRequest($url);
        $this->cacheResponse($cacheKey, $response);

        return $response;
    }

    /**
     * Build the API URL with query parameters
     * 
     * @param array $params Query parameters
     * @return string Complete API URL
     */
    private function buildUrl(array $params): string
    {
        return $this->baseUrl . '?' . http_build_query($params);
    }

    /**
     * Execute HTTP request to the NYT API
     * 
     * @param string $url The complete API URL
     * @return array Decoded API response
     */
    private function makeRequest(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->logger->error('API request failed', [
                'error' => curl_error($ch),
                'url' => $url
            ]);
            curl_close($ch);
            return ['status' => 'error', 'message' => 'API request failed'];
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            $this->logger->error('API returned non-200 status', [
                'status' => $httpCode,
                'url' => $url
            ]);
            return ['status' => 'error', 'message' => 'API returned status ' . $httpCode];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to parse API response', [
                'error' => json_last_error_msg()
            ]);
            return ['status' => 'error', 'message' => 'Failed to parse API response'];
        }

        $this->logger->info('API request successful', ['url' => $url]);
        return $data;
    }

    /**
     * Generate a unique cache key for API requests
     * 
     * @param string $type Request type (search/details)
     * @param array $params Request parameters
     * @return string MD5 hash of the cache key
     */
    private function generateCacheKey(string $type, array $params): string
    {
        return md5($type . json_encode($params));
    }

    /**
     * Retrieve cached API response if available
     * 
     * @param string $cacheKey The cache key to look up
     * @return array|null Cached response or null if not found/expired
     */
    private function getCachedResponse(string $cacheKey): ?array
    {
        $stmt = $this->db->prepare("
            SELECT response_data 
            FROM api_cache 
            WHERE request_hash = :hash 
            AND expires_at > CURRENT_TIMESTAMP
        ");

        $stmt->execute([':hash' => $cacheKey]);
        $cache = $stmt->fetch();

        return $cache ? json_decode($cache['response_data'], true) : null;
    }

    /**
     * Store API response in cache
     * 
     * @param string $cacheKey The cache key
     * @param array $response The API response to cache
     */
    private function cacheResponse(string $cacheKey, array $response): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                DELETE FROM api_cache 
                WHERE request_hash = :hash
            ");
            $stmt->execute([':hash' => $cacheKey]);

            $stmt = $this->db->prepare("
                INSERT INTO api_cache (
                    request_hash, 
                    response_data, 
                    expires_at
                ) VALUES (
                    :hash, 
                    :data, 
                    datetime('now', '+' || :duration || ' seconds')
                )
            ");

            $stmt->execute([
                ':hash' => $cacheKey,
                ':data' => json_encode($response),
                ':duration' => $this->cacheDuration
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to cache API response', [
                'error' => $e->getMessage(),
                'cache_key' => $cacheKey
            ]);
        }
    }
}
