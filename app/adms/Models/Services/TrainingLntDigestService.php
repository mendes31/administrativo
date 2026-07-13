<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\TrainingLntEventsRepository;

/**
 * Envia o relatório diário de eventos LNT (e-mail) no primeiro acesso do dia,
 * sem depender de cron — mesmo padrão de {@see PayrollDocumentRemindersService}.
 *
 * Persistência: storage/cache/system/training_lnt_digest_last_run.json
 *
 * Chamadas típicas:
 * - Login / Dashboard — no máximo 1× por dia civil (primeiro acesso após meia-noite)
 * - CLI {@see \App\adms\Controllers\Services\TrainingLntEventsDigestCli} com $force = true
 */
final class TrainingLntDigestService
{
    public static function ensureUpdated(bool $force = false): void
    {
        try {
            $today = date('Y-m-d');
            $cacheFile = self::cacheFilePath();

            if (!$force && file_exists($cacheFile)) {
                $content = file_get_contents($cacheFile);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data) && ($data['last_run_date'] ?? '') === $today) {
                        return;
                    }
                }
            }

            $service = new TrainingLntEventService();
            $repo = new TrainingLntEventsRepository();
            $dates = $repo->getPendingDigestDatesBefore($today);

            $totalEvents = 0;
            $totalEmails = 0;
            $datesProcessed = [];

            foreach ($dates as $refDate) {
                $result = $service->sendDailyDigest($refDate);
                $totalEvents += (int)($result['events'] ?? 0);
                $totalEmails += (int)($result['sent'] ?? 0);
                $datesProcessed[] = $refDate;
            }

            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            $payload = [
                'last_run_date' => $today,
                'last_run' => time(),
                'datetime' => date('Y-m-d H:i:s'),
                'dates_processed' => $datesProcessed,
                'events' => $totalEvents,
                'emails_sent' => $totalEmails,
            ];

            file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            error_log('TrainingLntDigestService::ensureUpdated error: ' . $e->getMessage());
        }
    }

    private static function cacheFilePath(): string
    {
        $projectRoot = dirname(__DIR__, 4);

        return $projectRoot
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'system'
            . DIRECTORY_SEPARATOR . 'training_lnt_digest_last_run.json';
    }
}
