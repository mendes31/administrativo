<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;

class DeleteInventoryCategory
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!isset($form['csrf_token']) || !\App\adms\Helpers\CSRFHelper::validateCSRFToken('form_delete_inventory_category', $form['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Requisição inválida.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-categories'); return;
        }
        $id = (int)($form['id'] ?? 0);
        if (!$id) { $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-categories'); return; }
        $repo = new InvCategoriesRepository();
        $_SESSION['msg'] = $repo->delete($id) ? "<div class='alert alert-success'>Categoria excluída.</div>" : "<div class='alert alert-danger'>Erro ao excluir.</div>";
        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-categories');
    }
}








