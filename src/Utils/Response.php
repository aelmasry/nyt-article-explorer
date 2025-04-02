<?php

namespace App\Utils;

class Response {
    /**
     * Send a JSON response
     */
    public static function json($data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send an error response
     */
    public static function error(string $message, int $statusCode = 400): void {
        self::json(['error' => $message], $statusCode);
    }
    
    /**
     * Send a success response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        $response = ['success' => true, 'message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $statusCode);
    }
    
    /**
     * Redirect to a URL
     */
    public static function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
    
    /**
     * Render an HTML page
     */
    public static function view(string $template, array $data = []): void {
        $templatePath = dirname(__DIR__, 2) . "/templates/$template.php";
        
        if (!file_exists($templatePath)) {
            self::error("Template not found: $template", 500);
        }
        
        // Extract data to make it available to the template
        extract($data);
        
        // Start output buffering
        ob_start();
        require $templatePath;
        $content = ob_get_clean();
        
        echo $content;
        exit;
    }
    
    /**
     * Send a Rate Limit response
     */
    public static function rateLimitExceeded(int $retryAfter): void {
        header("Retry-After: $retryAfter");
        self::error("Rate limit exceeded. Try again after $retryAfter seconds.", 429);
    }
}