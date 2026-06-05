<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvLaborRolesRepository;

class DeleteInventoryLaborRole
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
            return;
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_delete_inventory_labor_role', $token)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
            return;
        }

        $repo = new InvLaborRolesRepository();
        if ($repo->delete($id)) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Papel excluído.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Não foi possível excluir (pode estar em uso na rota).</div>";
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
    }
}
