<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstRiscoExameRepository;

class SstDeleteRiscoExame
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_risco_exame', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
            exit;
        }
        if ((new SstRiscoExameRepository())->delete($id)) {
            $_SESSION['msg'] = 'Registro excluído com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível excluir o registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
        exit;
    }
}
