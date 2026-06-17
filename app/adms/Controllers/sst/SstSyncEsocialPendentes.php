<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstEsocialPayloadService;

class SstSyncEsocialPendentes
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-report-conformidade');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sst_esocial_actions', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-report-conformidade');
            exit;
        }

        $result = (new SstEsocialPayloadService())->sincronizarPendentes();
        $msg = $result['criados'] . ' evento(s) gerado(s).';
        if (!empty($result['erros'])) {
            $msg .= ' Erros: ' . count($result['erros']);
            $_SESSION['msg_type'] = 'warning';
        } else {
            $_SESSION['msg_type'] = 'success';
        }
        $_SESSION['msg'] = $msg;
        if (!empty($result['erros'])) {
            $_SESSION['esocial_sync_erros'] = $result['erros'];
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sst-report-conformidade');
        exit;
    }
}
