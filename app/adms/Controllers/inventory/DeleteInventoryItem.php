<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvItemsRepository;

class DeleteInventoryItem
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!isset($form['csrf_token']) || !CSRFHelper::validateCSRFToken('form_delete_inventory_item', $form['csrf_token'])) {
            $_SESSION['error'] = 'Ação não autorizada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $id = isset($form['id']) ? (int)$form['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $repo = new InvItemsRepository();
        if ($repo->delete($id)) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Item apagado com sucesso.</div>";
        } else {
            $_SESSION['error'] = 'Erro ao apagar o item.';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
    }
}









