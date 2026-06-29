<?php

namespace App\adms\Models\Services;

class InventorySapSyncWebRunner
{
    /**
     * @param array{full?: bool, code?: string, group?: string, auto_continue?: bool} $options
     */
    public static function spawn(int $runId, string $type, array $options = []): bool
    {
        if ($runId <= 0) {
            return false;
        }

        // Spawn só a partir de CLI; requisições web usam fase execute (fetch + flush).
        if (PHP_SAPI !== 'cli') {
            return false;
        }

        $php = self::resolvePhpCliBinary();
        $script = self::resolveScriptPath();
        if (!is_file($script)) {
            return false;
        }

        $args = [
            '--run-id=' . $runId,
            $type === 'structures' ? '--structures' : '--items',
        ];

        if ($type === 'items') {
            if (!empty($options['full'])) {
                $args[] = '--full';
            }
            if (empty($options['auto_continue'])) {
                $args[] = '--no-auto-continue';
            }
            $code = trim((string)($options['code'] ?? ''));
            $group = trim((string)($options['group'] ?? ''));
            if ($code !== '') {
                $args[] = '--code=' . $code;
            } elseif ($group !== '') {
                $args[] = '--group=' . $group;
            }
        }

        $commandParts = array_merge([$php, $script], $args);
        $command = '';
        foreach ($commandParts as $part) {
            $command .= ($command === '' ? '' : ' ') . escapeshellarg((string)$part);
        }
        $logFile = self::logFilePath($runId);
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);
        $processEnv = self::buildProcessEnvironment();

        if (DIRECTORY_SEPARATOR === '\\') {
            $winCmd = 'cmd /C start "" /B cd /d ' . escapeshellarg($root)
                . ' && ' . $command . ' >> ' . escapeshellarg($logFile) . ' 2>&1';
            $handle = @popen($winCmd, 'rb');
            if (is_resource($handle)) {
                pclose($handle);
                return true;
            }

            return self::spawnWithProcOpen($command, $logFile, $root, $processEnv);
        }

        return self::spawnWithProcOpen(
            $command . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &',
            $logFile,
            $root,
            $processEnv,
            true
        );
    }

    /**
     * Repassa variáveis já carregadas pelo Apache para o processo CLI.
     *
     * @return array<string, string>|null
     */
    private static function buildProcessEnvironment(): ?array
    {
        $keys = [
            'DB_HOST', 'DB_NAME', 'DB_DATABASE', 'DB_USER', 'DB_PASS', 'DB_PORT',
            'APP_TIMEZONE', 'APP_ENV', 'APP_NAME',
            'SAP_API_URL', 'SAP_API_SCHEMA', 'SAP_API_USER', 'SAP_API_PASS', 'SAP_API_PASSWORD',
        ];

        $env = [];
        foreach ($keys as $key) {
            if (isset($_ENV[$key]) && (is_string($_ENV[$key]) || is_numeric($_ENV[$key]))) {
                $env[$key] = (string) $_ENV[$key];
            } elseif (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
                $env[$key] = $_SERVER[$key];
            }
        }

        if (($env['DB_NAME'] ?? '') === '' && isset($env['DB_DATABASE'])) {
            $env['DB_NAME'] = $env['DB_DATABASE'];
        }

        return $env !== [] ? $env : null;
    }

    /**
     * @param array<string, string>|null $processEnv
     */
    private static function spawnWithProcOpen(
        string $command,
        string $logFile,
        string $workingDirectory,
        ?array $processEnv = null,
        bool $shell = false
    ): bool {
        if (!function_exists('proc_open')) {
            return false;
        }

        $descriptorSpec = [
            0 => ['file', 'NUL', 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ];
        if (DIRECTORY_SEPARATOR !== '\\') {
            $descriptorSpec[0] = ['file', '/dev/null', 'r'];
        }

        $process = @proc_open(
            $command,
            $descriptorSpec,
            $pipes,
            $workingDirectory,
            $processEnv,
            $shell ? ['bypass_shell' => false] : ['bypass_shell' => true]
        );
        if (!is_resource($process)) {
            return false;
        }

        proc_close($process);

        return true;
    }

    private static function resolvePhpCliBinary(): string
    {
        $configured = trim((string)($_ENV['PHP_CLI_PATH'] ?? ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_file(PHP_BINARY) && !str_contains(strtolower(PHP_BINARY), 'apache')) {
            return PHP_BINARY;
        }

        $wampPattern = 'C:\\wamp64\\bin\\php\\php'
            . PHP_MAJOR_VERSION . '.'
            . PHP_MINOR_VERSION . '.'
            . PHP_RELEASE_VERSION
            . '\\php.exe';
        if (is_file($wampPattern)) {
            return $wampPattern;
        }

        return 'php';
    }

    private static function resolveScriptPath(): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        return $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_inventory_sap_sync_web.php';
    }

    private static function logFilePath(int $runId): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);
        $dir = $root . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . DIRECTORY_SEPARATOR . 'sap_sync_web_' . $runId . '.log';
    }
}
