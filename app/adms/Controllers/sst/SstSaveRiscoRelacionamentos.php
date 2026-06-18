<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstRiscoExameRepository;
use App\adms\Models\Repository\SstRiscoEpiRepository;
use App\adms\Models\Repository\SstRiscosRepository;

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
            header('Location: ' . $redirect);
            exit;
        }

        if ($riscoId <= 0 || !(new SstRiscosRepository())->getById($riscoId)) {
            $_SESSION['msg'] = 'Risco não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }

        $exameIds = is_array($_POST['exames'] ?? null) ? array_map('intval', $_POST['exames']) : [];
        $epiIds = is_array($_POST['epis'] ?? null) ? array_map('intval', $_POST['epis']) : [];

        (new SstRiscoExameRepository())->syncExamesForRisco($riscoId, $exameIds);
        (new SstRiscoEpiRepository())->syncEpisForRisco($riscoId, $epiIds);

        $_SESSION['msg'] = 'Relacionamentos salvos com sucesso.';
        $_SESSION['msg_type'] = 'success';
        $tab = in_array($_POST['active_tab'] ?? '', ['exames', 'epis'], true) ? $_POST['active_tab'] : 'exames';
        header('Location: ' . $redirect . '#tab-' . $tab);
        exit;
    }
}
