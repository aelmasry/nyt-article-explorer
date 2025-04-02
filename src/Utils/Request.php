<?php

namespace App\Utils;

class Request {
    private array $queryParams;
    private array $bodyParams;
    private array $files;
    
    public function __construct() {
        $this->queryParams = $_GET;
        $this->bodyParams = $this->parseInput();
        $this->files = $_FILES;
    }
    
    private function parseInput(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = file_get_contents('php://input');
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($input, true) ?? [];
        }
        
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($input, $data);
            return $data;
        }
        
        return $_POST;
    }
    
    /**
     * الحصول على معلمة من الاستعلام (GET)
     */
    public function getQuery(string $key, $default = null) {
        return $this->queryParams[$key] ?? $default;
    }
    
    /**
     * الحصول على كافة معلمات الاستعلام
     */
    public function getAllQuery(): array {
        return $this->queryParams;
    }
    
    /**
     * الحصول على معلمة من البيانات (POST/PUT)
     */
    public function getBody(string $key, $default = null) {
        return $this->bodyParams[$key] ?? $default;
    }
    
    /**
     * الحصول على كافة معلمات البيانات
     */
    public function getAllBody(): array {
        return $this->bodyParams;
    }
    
    /**
     * الحصول على ملف مرفق
     */
    public function getFile(string $key) {
        return $this->files[$key] ?? null;
    }
    
    /**
     * الحصول على المعرف الفريد للمستخدم (IP أو معرف المستخدم)
     */
    public function getClientIdentifier(): string {
        // إذا كان المستخدم مسجلاً، استخدم معرفه
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            return "user_$userId";
        }
        
        // وإلا استخدم عنوان IP
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * الحصول على عنوان IP للمستخدم
     */
    public function getIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * الحصول على طريقة الطلب
     */
    public function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * الحصول على مسار الطلب
     */
    public function getPath(): string {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    }
}