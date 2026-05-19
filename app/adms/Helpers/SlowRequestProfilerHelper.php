<?php

namespace App\adms\Helpers;

use App\adms\Models\Repository\AdmsLogSettingsRepository;
use App\adms\Models\Repository\AdmsSlowRequestProfileRepository;

final class SlowRequestProfilerHelper
{
    /** @var array{enabled: bool, threshold_ms: int, retention_days: int}|null */
    private static ?array $profilerConfig = null;

    public static function registerRequestStart(): void
    {
        if (!defined('ADMS_REQUEST_START_TS')) {
            define('ADMS_REQUEST_START_TS', microtime(true));
        }
    }

    public static function registerShutdownProfiler(): void
    {
        register_shutdown_function(static function (): void {
            self::profileCurrentRequest();
        });
    }

    private static function profileCurrentRequest(): void
    {
        try {
            if (!defined('ADMS_REQUEST_START_TS')) {
                return;
            }

            $config = self::getProfilerConfig();
            if (!$config['enabled']) {
                return;
            }

            $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
            $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if (self::shouldSkip($uri, $method)) {
                return;
            }

            $durationMs = (int)round((microtime(true) - ADMS_REQUEST_START_TS) * 1000);
            if ($durationMs < $config['threshold_ms']) {
                return;
            }

            self::debugLog('log', [
                'uri' => $uri,
                'method' => $method,
                'duration_ms' => $durationMs,
                'threshold_ms' => $config['threshold_ms'],
                'user_id' => $_SESSION['user_id'] ?? null,
            ]);

            $profileRepo = new AdmsSlowRequestProfileRepository();
            $profileRepo->logSlowRequest([
                'request_method' => $method,
                'request_uri' => self::normalizeUri($uri),
                'route_label' => self::extractRouteLabel($uri),
                'user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
                'duration_ms' => $durationMs,
                'memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            ]);

            if (random_int(1, 25) === 1) {
                $profileRepo->cleanupOldProfiles($config['retention_days']);
            }
        } catch (\Throwable $e) {
            self::debugLog('error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * @return array{enabled: bool, threshold_ms: int, retention_days: int}
     */
    private static function getProfilerConfig(): array
    {
        if (self::$profilerConfig !== null) {
            return self::$profilerConfig;
        }

        $repo = new AdmsLogSettingsRepository();
        self::$profilerConfig = [
            'enabled' => $repo->isSlowProfilerEnabled(),
            'threshold_ms' => $repo->getSlowProfilerThresholdMs(),
            'retention_days' => $repo->getSlowProfilerRetentionDays(),
        ];

        return self::$profilerConfig;
    }

    private static function debugLog(string $type, array $data): void
    {
        try {
            $line = date('Y-m-d H:i:s') . ' [' . $type . '] ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Caminho principal: app/logs dentro do projeto (relativo a app/adms/Helpers)
            $primaryPath = __DIR__ . '/../../logs/slow_profiler_debug.log';
            if (@file_put_contents($primaryPath, $line . PHP_EOL, FILE_APPEND) === false) {
                // Fallback: diretório temporário do PHP (quase sempre gravável na hospedagem)
                $fallbackPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'slow_profiler_debug.log';
                @file_put_contents($fallbackPath, $line . PHP_EOL, FILE_APPEND);
            }
        } catch (\Throwable) {
            // silencioso
        }
    }

    private static function shouldSkip(string $uri, string $method): bool
    {
        if ($method === 'OPTIONS') {
            return true;
        }
        if ($uri === '') {
            return true;
        }
        if (strpos($uri, '/public/') !== false) {
            return true;
        }
        return (bool)preg_match('/\.(?:css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|map)$/i', $uri);
    }

    private static function normalizeUri(string $uri): string
    {
        $clean = strtok($uri, '#');
        return substr((string)$clean, 0, 1024);
    }

    private static function extractRouteLabel(string $uri): string
    {
        $path = (string)parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path === '') {
            return 'root';
        }
        $chunks = explode('/', $path);
        return (string)($chunks[count($chunks) - 1] ?? 'unknown');
    }
}
