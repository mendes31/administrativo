<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvStocksRepository;

class DeleteInventoryStock
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!isset($form['csrf_token']) || !CSRFHelper::validateCSRFToken('form_delete_inventory_stock', $form['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Requisição inválida.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-stocks'); return;
        }
        $id = (int)($form['id'] ?? 0);
        if (!$id) { $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-stocks'); return; }
        $repo = new InvStocksRepository();
        $_SESSION['msg'] = $repo->delete($id) ? "<div class='alert alert-success'>Estoque excluído.</div>" : "<div class='alert alert-danger'>Erro ao excluir.</div>";
        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-stocks');
    }
}








