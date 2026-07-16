<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Services\WhistleblowingSlaBreachService;
use App\adms\Models\Services\WhistleblowingRetentionService;

/**
 * Cron HTTP para retenção LGPD de denúncias.
 * GET whistleblowing-retention-cron?token=...
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
        if (!$configRepo->isCronEnabled()) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Cron desativado na configuração do canal.\n";
            exit;
        }

        $slaBreaches = 0;
        try {
            $result = (new WhistleblowingRetentionService())->run('cron');
            $slaBreaches = (new WhistleblowingSlaBreachService())->processPendingBreaches();
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
            . ' sla_alerts=' . (int) ($slaBreaches ?? 0)
            . ' ms=' . (int) ($result['duration_ms'] ?? 0) . "\n";
        exit;
    }
}
