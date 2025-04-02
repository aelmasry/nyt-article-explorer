<?php

namespace App\Utils;

use App\Config\Database;
use PDO;

class Logger
{
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_DEBUG = 'DEBUG';

    private PDO $db;
    private string $logFile;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logFile = dirname(__DIR__, 2) . '/logs/app.log';

        // التأكد من وجود مجلد السجلات
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * تسجيل رسالة في قاعدة البيانات وملف السجل
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = !empty($context) ? json_encode($context) : null;

        // تسجيل في قاعدة البيانات
        $stmt = $this->db->prepare("
            INSERT INTO logs (level, message, context, timestamp) 
            VALUES (:level, :message, :context, :timestamp)
        ");

        $stmt->execute([
            ':level' => $level,
            ':message' => $message,
            ':context' => $contextJson,
            ':timestamp' => $timestamp
        ]);

        // تسجيل في ملف
        $logLine = "[$timestamp] [$level] $message";
        if (!empty($context)) {
            $logLine .= " " . json_encode($context);
        }
        $logLine .= PHP_EOL;

        file_put_contents($this->logFile, $logLine, FILE_APPEND);
    }

    /**
     * تسجيل رسالة معلومات
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * تسجيل رسالة تحذير
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * تسجيل رسالة خطأ
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * تسجيل رسالة تصحيح
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * تسجيل طلب API
     */
    public function logApiRequest(string $method, string $path, array $params = []): void
    {
        $this->info("API Request: $method $path", ['params' => $params]);
    }

    /**
     * تسجيل استجابة API
     */
    public function logApiResponse(string $method, string $path, int $statusCode, array $response = []): void
    {
        $this->info(
            "API Response: $method $path - Status: $statusCode",
            ['response' => $response]
        );
    }
}
