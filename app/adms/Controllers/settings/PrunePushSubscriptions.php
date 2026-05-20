<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Repository\PushSubscriptionRepository;
use App\adms\Models\Services\PushNotificationService;

class PrunePushSubscriptions
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_push_prune', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $configRepo = new AdmsPushConfigRepository();
        if (!$configRepo->isEnabled()) {
            $_SESSION['msg'] = 'Ative o push e configure VAPID antes de verificar inscrições.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $before = (new PushSubscriptionRepository())->countAll();
        $summary = (new PushNotificationService())->pruneExpiredSubscriptions(200);
        $after = (new PushSubscriptionRepository())->countAll();

        $removed = (int) ($summary['removed'] ?? 0);
        $checked = (int) ($summary['checked'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);

        if ($removed > 0) {
            $_SESSION['msg'] = "Verificadas {$checked} inscrição(ões) (lote). Removidas {$removed} inválida(s). Total no banco: {$before} → {$after}. Detalhes em logs/push_" . date('dmY') . '.log';
            $_SESSION['msg_type'] = 'success';
        } elseif ($checked === 0) {
            $_SESSION['msg'] = 'Nenhuma inscrição push cadastrada no sistema.';
            $_SESSION['msg_type'] = 'info';
        } elseif ($failed > 0) {
            $_SESSION['msg'] = "Verificadas {$checked} inscrição(ões). Nenhuma removida; {$failed} falha(s) de rede/VAPID. Veja logs/push_" . date('dmY') . '.log';
            $_SESSION['msg_type'] = 'warning';
        } else {
            $_SESSION['msg'] = "Verificadas {$checked} inscrição(ões) no lote — todas ainda válidas. Total no banco: {$after}.";
            $_SESSION['msg_type'] = 'info';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
        exit;
    }
}
