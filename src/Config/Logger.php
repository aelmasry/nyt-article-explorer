<?php

namespace App\Config;

class Logger
{
    private static $instance = null;
    private $logPath;
    private $logLevel;

    private function __construct()
    {
        $this->logPath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../../logs/app.log';
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'info';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function setLogPath(string $path): void
    {
        $this->logPath = $path;
    }

    public function setLogLevel(string $level): void
    {
        $this->logLevel = $level;
    }
} 