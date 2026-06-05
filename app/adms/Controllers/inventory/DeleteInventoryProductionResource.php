<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;

class DeleteInventoryProductionResource
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-production-resources');
            return;
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_delete_inventory_production_resource', $token)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-production-resources');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>ID inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-production-resources');
            return;
        }

        $repo = new InvProductionResourcesRepository();
        if ($repo->delete($id)) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Recurso excluído.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Não foi possível excluir (pode estar em uso na rota).</div>";
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-production-resources');
    }
}
