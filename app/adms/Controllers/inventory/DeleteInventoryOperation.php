<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\inventory\InvOperationsRepository;

class DeleteInventoryOperation
{
    public function index(): void
    {
        $data = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!isset($data['csrf_token']) ||
            !CSRFHelper::validateCSRFToken('form_delete_inventory_operation', $data['csrf_token'])) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-operations');
            return;
        }

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Operação inválida.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-operations');
            return;
        }

        $repo = new InvOperationsRepository();
        $ok = $repo->delete($id);

        if ($ok) {
            $_SESSION['success'] = 'Operação excluída com sucesso.';
        } else {
            $_SESSION['error'] = 'Erro ao excluir a operação.';
            GenerateLog::generateLog('error', 'Falha ao excluir operação de estoque (controller)', ['id' => $id]);
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-operations');
    }
}

