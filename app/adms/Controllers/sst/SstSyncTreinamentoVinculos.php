<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstTreinamentoVinculoSyncService;

class SstSyncTreinamentoVinculos
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->sync();
            return;
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
        exit;
    }

    private function sync(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_sync_treinamento_vinculos', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
            exit;
        }

        $service = new SstTreinamentoVinculoSyncService();
        $userId = (int) ($_POST['adms_user_id'] ?? 0);
        if ($userId > 0) {
            $result = $service->syncForUser($userId);
            $_SESSION['msg'] = sprintf(
                'Sincronização concluída: %d vínculo(s) criado(s), %d já existente(s).',
                $result['criados'],
                $result['existentes']
            );
        } else {
            $result = $service->syncForAllActiveUsers();
            $_SESSION['msg'] = sprintf(
                'Sincronização em lote: %d colaborador(es), %d vínculo(s) criado(s), %d já existente(s).',
                $result['usuarios'],
                $result['criados'],
                $result['existentes']
            );
        }
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
        exit;
    }
}
