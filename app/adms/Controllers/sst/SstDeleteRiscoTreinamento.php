<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
use App\adms\Models\Repository\SstRiscoTreinamentoRepository;

class SstDeleteRiscoTreinamento
{
    public function index(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $repo = new SstRiscoTreinamentoRepository();
        $row = $id > 0 ? $repo->getById($id) : null;
        $riscoId = (int) ($_POST['return_risco_id'] ?? $row['adms_sst_risco_id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_risco_treinamento', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Requisição inválida.';
            $_SESSION['msg_type'] = 'danger';
            SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'treinamentos', 'sst-list-riscos');
        }
        if ($repo->delete($id)) {
            $_SESSION['msg'] = 'Registro excluído.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao excluir registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'treinamentos', 'sst-list-riscos');
    }
}
