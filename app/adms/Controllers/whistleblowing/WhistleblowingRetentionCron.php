<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Services\WhistleblowingSlaBreachService;
use App\adms\Models\Services\WhistleblowingRetentionService;

/**
 * Cron HTTP do Canal de Denúncias.
 * GET whistleblowing-retention-cron?token=...
 *
 * - Alertas de SLA / inatividade: sempre processados quando o token é válido.
 * - Retenção LGPD: só se «Retenção automática» estiver ativa na configuração.
 *
 * Token: banco (whistleblowing-config) ou CRON_WHISTLEBLOWING_TOKEN no .env
 */
final class WhistleblowingRetentionCron
{
    public function index(): void
    {
        $secret = '';
        try {
            $secret = (new WhistleblowingConfigRepository())->getHttpCronToken();
        } catch (\Throwable) {
        }
        if ($secret === '') {
            $secret = trim((string) ($_ENV['CRON_WHISTLEBLOWING_TOKEN'] ?? ''));
        }

        $token = trim((string) ($_GET['token'] ?? ''));

        if ($secret === '' || $token === '' || !hash_equals($secret, $token)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Acesso negado. Configure o token em Canal de Denúncias → Configuração ou CRON_WHISTLEBLOWING_TOKEN no .env\n";
            exit;
        }

        $configRepo = new WhistleblowingConfigRepository();
        $retentionEnabled = $configRepo->isCronEnabled();

        $result = [
            'archived' => 0,
            'deleted' => 0,
            'attachments_deleted' => 0,
            'duration_ms' => 0,
        ];
        $slaBreaches = 0;

        try {
            // Alertas independentes da retenção LGPD.
            $slaBreaches = (new WhistleblowingSlaBreachService())->processPendingBreaches();

            if ($retentionEnabled) {
                $result = (new WhistleblowingRetentionService())->run('cron');
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'ERROR ' . $e->getMessage() . "\n";
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo 'OK archived=' . (int) ($result['archived'] ?? 0)
            . ' deleted=' . (int) ($result['deleted'] ?? 0)
            . ' attachments=' . (int) ($result['attachments_deleted'] ?? 0)
            . ' sla_alerts=' . (int) $slaBreaches
            . ' retention=' . ($retentionEnabled ? 'on' : 'off')
            . ' ms=' . (int) ($result['duration_ms'] ?? 0) . "\n";
        exit;
    }
}
