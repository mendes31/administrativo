<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Log dedicado ao Web Push (monitoramento em produção).
 * Arquivo: logs/push_dmY.log (ex.: logs/push_20052026.log)
 */
class PushNotificationLog
{
    public static function log(string $level, string $message, ?array $context = null): void
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3);
        $dir = $root . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filePath = $dir . DIRECTORY_SEPARATOR . 'push_' . date('dmY') . '.log';
        if (!file_exists($filePath)) {
            touch($filePath);
        }

        $monologLevel = match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };

        $logger = new Logger('web_push');
        $logger->pushHandler(new StreamHandler($filePath, Level::Debug));
        $logger->log($monologLevel, $message, $context ?? []);
    }
}
