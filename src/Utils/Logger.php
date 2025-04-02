<?php

namespace App\Utils;

use App\Config\Logger as LoggerConfig;

class Logger
{
    private $config;
    private $logLevels = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    ];

    public function __construct()
    {
        $this->config = LoggerConfig::getInstance();
    }

    /**
     * Log an emergency message
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Log an alert message
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log a notice message
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log a message at the specified level
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logLevels[$level] > $this->logLevels[$this->config->getLogLevel()]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        file_put_contents(
            $this->config->getLogPath(),
            $logMessage,
            FILE_APPEND
        );
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
