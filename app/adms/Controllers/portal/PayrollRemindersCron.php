<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Models\Repository\PayrollCronConfigRepository;
use App\adms\Models\Services\PayrollDocumentRemindersService;

/**
 * Endpoint para agendador (cron) disparar lembretes de ciência.
 *
 * URL (com hífens): .../payroll-reminders-cron?token=VALOR_GUARDADO_EM_RH_>_Configuração_cron
 * O token define-se na página payroll-cron-config (base de dados), não no .env.
 */
final class PayrollRemindersCron
{
    public function index(string|null $param = null): void
    {
        $secret = (new PayrollCronConfigRepository())->getHttpCronToken();
        $token = (string)($_GET['token'] ?? '');

        if ($secret === '' || $token === '' || !hash_equals($secret, $token)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/');
            echo "Acesso negado. Defina o token em: Configuração do cron de lembretes (RH) — " . $base . "/payroll-cron-config\n";
            echo 'Chamada: ' . $base . "/payroll-reminders-cron?token=...\n";
            echo "Em alternativa: php scripts/payroll_reminders_cron.php (CLI, sem token).\n";
            exit;
        }

        PayrollDocumentRemindersService::ensureUpdated(true);
        $sent = 0;
        $cacheFile = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'payroll_reminders_last_run.json';
        if (is_readable($cacheFile)) {
            $j = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($j) && isset($j['reminders_sent'])) {
                $sent = (int) $j['reminders_sent'];
            }
        }
        header('Content-Type: text/plain; charset=utf-8');
        echo 'OK reminders_sent=' . $sent . "\n";
        exit;
    }
}
