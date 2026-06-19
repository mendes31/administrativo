<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstRiscoEpiRepository;

class SstSaveRiscoRelacionamentos
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }

        $riscoId = (int) ($_POST['adms_sst_risco_id'] ?? 0);
        $redirect = $_ENV['URL_ADM'] . 'sst-view-risco/' . $riscoId;

        if (!CSRFHelper::validateCSRFToken('sst_risco_relacionamentos', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect . '#tab-epis');
            exit;
        }

        $epiIds = is_array($_POST['epis'] ?? null) ? array_map('intval', $_POST['epis']) : [];
        $obrigatorioPost = is_array($_POST['epis_obrigatorio'] ?? null) ? $_POST['epis_obrigatorio'] : [];
        $epiMap = [];
        foreach ($epiIds as $epiId) {
            if ($epiId > 0) {
                $epiMap[$epiId] = ['obrigatorio' => isset($obrigatorioPost[$epiId])];
            }
        }

        (new SstRiscoEpiRepository())->syncEpisForRisco($riscoId, $epiMap);

        $_SESSION['msg'] = 'EPIs vinculados salvos com sucesso.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirect . '#tab-epis');
        exit;
    }
}
