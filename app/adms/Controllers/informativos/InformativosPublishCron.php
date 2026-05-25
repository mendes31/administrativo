<?php

declare(strict_types=1);

namespace App\adms\Controllers\informativos;

use App\adms\Models\Services\InformativosStatusUpdaterService;

/**
 * Endpoint HTTP para cron externo ativar informativos agendados e disparar push.
 *
 * Chamada: GET .../informativos-publish-cron?token=<CRON_INFORMATIVOS_TOKEN>
 * Recomendado: a cada 1–2 minutos via crontab, Task Scheduler ou similar.
 */
final class InformativosPublishCron
{
    public function index(string|null $param = null): void
    {
        $secret = trim((string) ($_ENV['CRON_INFORMATIVOS_TOKEN'] ?? ''));
        $token  = trim((string) ($_GET['token'] ?? ''));

        if ($secret === '' || $token === '' || !hash_equals($secret, $token)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Acesso negado. Defina CRON_INFORMATIVOS_TOKEN no .env e passe ?token=...\n";
            exit;
        }

        InformativosStatusUpdaterService::ensureUpdated(true);

        $cacheFile = dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'system'
            . DIRECTORY_SEPARATOR . 'informativos_status_last_run.json';

        $result = ['ativados' => 0, 'inativados' => 0];
        if (is_readable($cacheFile)) {
            $j = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($j) && isset($j['updated']) && is_array($j['updated'])) {
                $result = $j['updated'];
            }
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo 'OK ativados=' . (int) ($result['ativados'] ?? 0)
            . ' inativados=' . (int) ($result['inativados'] ?? 0) . "\n";
        exit;
    }
}
