<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;

class SstDeleteEquipamentoTipo
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_equipamento_tipos', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
            exit;
        }
        $repo = new SstEquipamentoTiposRepository();
        if ($repo->countEquipamentos($id) > 0) {
            $_SESSION['msg'] = 'Não é possível excluir: existem equipamentos vinculados.';
            $_SESSION['msg_type'] = 'danger';
        } else {
            $repo->delete($id);
            $_SESSION['msg'] = 'Tipo excluído.';
            $_SESSION['msg_type'] = 'success';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
        exit;
    }
}
