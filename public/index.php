<?php
/**
 * Main Router
 * Author: Ali Salem <admin@alisalem.me>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RateLimitMiddleware;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get request path and method
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// If the request is for the API
if (strpos($path, '/api') === 0) {
    // Set API headers
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Handle preflight requests
    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Initialize controllers
    $apiController = new ApiController();
    $authController = new AuthController();

    // Initialize middlewares
    $authMiddleware = new AuthMiddleware();
    $rateLimitMiddleware = new RateLimitMiddleware();

    // Remove /api prefix from path
    $path = substr($path, 4);

    // Apply rate limiting to all API requests
    $rateLimitMiddleware->handle();

    // Define API routes
    $routes = [
        // Auth routes (no authentication required)
        'POST /auth/login' => [$authController, 'login'],
        'POST /auth/register' => [$authController, 'register'],

        // API routes (authentication required)
        'GET /articles/search' => [$apiController, 'searchArticles'],
        'GET /articles/details' => [$apiController, 'getArticleDetails'],
        'POST /favorites' => [$apiController, 'addFavorite'],
        'DELETE /favorites' => [$apiController, 'removeFavorite'],
        'GET /favorites' => [$apiController, 'getFavorites']
    ];

    // Find matching route
    $routeKey = $method . ' ' . $path;
    $handler = $routes[$routeKey] ?? null;

    if (!$handler) {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        exit;
    }

    // Apply authentication middleware for protected routes
    if (!in_array($routeKey, ['POST /auth/login', 'POST /auth/register'])) {
        $userData = $authMiddleware->handle();
        if (!$userData) {
            exit;
        }
    }

    // Execute route handler
    try {
        call_user_func($handler);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
} else {
    // Serve frontend for all other routes
    header('Content-Type: text/html');
    readfile(__DIR__ . '/index.html');
}
