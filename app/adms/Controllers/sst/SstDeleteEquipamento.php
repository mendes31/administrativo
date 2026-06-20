<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEquipamentosRepository;

class SstDeleteEquipamento
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_equipamentos', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }
        (new SstEquipamentosRepository())->delete($id);
        $_SESSION['msg'] = 'Equipamento excluído.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
        exit;
    }
}
