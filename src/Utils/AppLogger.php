<?php

namespace App\Utils;

class AppLogger
{
    private static string $logDir;

    private static function init()
    {
        if (!isset(self::$logDir)) {
            self::$logDir = __DIR__ . '/../../logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] $level: $message$contextString" . PHP_EOL;

        // Log to daily file
        $date = date('Y-m-d');
        file_put_contents(self::$logDir . "/app-$date.log", $logEntry, FILE_APPEND);

        // Log errors to separate file
        if ($level === 'ERROR' || $level === 'CRITICAL') {
            file_put_contents(self::$logDir . '/error.log', $logEntry, FILE_APPEND);
        }

        // Log to stdout in development
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            file_put_contents('php://stdout', $logEntry);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    public static function logException(\Throwable $e, string $message = 'Exception occurred'): void
    {
        self::error($message, [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
