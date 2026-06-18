<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstRiscoEpiRepository;

class SstDeleteRiscoEpi
{
    public function index(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_risco_epi', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Requisição inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-epi');
            exit;
        }
        $repo = new SstRiscoEpiRepository();
        if ($repo->delete($id)) {
            $_SESSION['msg'] = 'Registro excluído.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao excluir registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-epi');
        exit;
    }
}
