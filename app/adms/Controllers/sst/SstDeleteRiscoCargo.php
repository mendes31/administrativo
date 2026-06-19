<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
use App\adms\Models\Repository\SstRiscoCargoRepository;

class SstDeleteRiscoCargo
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SstRiscoNavigationHelper::redirectAfterMutation(0, 'cargos', 'sst-list-riscos');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $repo = new SstRiscoCargoRepository();
        $row = $id > 0 ? $repo->getById($id) : null;
        $riscoId = (int) ($_POST['return_risco_id'] ?? $row['adms_sst_risco_id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_riscos_cargo', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'cargos', 'sst-list-riscos');
        }
        if ($repo->delete($id)) {
            $_SESSION['msg'] = 'Registro excluído com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível excluir o registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'cargos', 'sst-list-riscos');
    }
}
