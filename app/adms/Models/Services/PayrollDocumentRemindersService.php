<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Dispara lembretes de ciência em documentos de folha (notificações internas, régua D+X por tipo),
 * no máximo uma vez por intervalo — mesmo padrão de {@see CandidateRetentionService}.
 *
 * Persistência: storage/cache/system/payroll_reminders_last_run.json
 *
 * Chamadas típicas:
 * - Login / Dashboard (intervalo 24 h) — primeiro acesso do dia em geral
 * - HTTP ou CLI com $force = true — ignora o intervalo
 */
final class PayrollDocumentRemindersService
{
    private const DEFAULT_INTERVAL_SECONDS = 86400; // 24 horas

    public static function ensureUpdated(bool $force = false, ?int $minIntervalSeconds = null): void
    {
        try {
            $projectRoot = dirname(__DIR__, 4);
            $cacheDir = $projectRoot
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'cache'
                . DIRECTORY_SEPARATOR . 'system';

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'payroll_reminders_last_run.json';
            $now = time();
            $interval = $minIntervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;

            if (!$force && file_exists($cacheFile)) {
                $content = file_get_contents($cacheFile);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['last_run'])) {
                        $lastRun = (int) $data['last_run'];
                        if (($now - $lastRun) < $interval) {
                            return;
                        }
                    }
                }
            }

            $sent = (new PayrollDocumentReminderJob())->run();

            $payload = [
                'last_run' => $now,
                'datetime' => date('Y-m-d H:i:s', $now),
                'reminders_sent' => $sent,
            ];

            file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            error_log('PayrollDocumentRemindersService::ensureUpdated error: ' . $e->getMessage());
        }
    }
}
