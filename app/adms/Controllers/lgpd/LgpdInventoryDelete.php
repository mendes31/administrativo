<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdInventoryRepository;

class LgpdInventoryDelete
{
    public function index(): void
    {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $csrf_token = $_POST['csrf_token'] ?? '';
            
            if ($id && CSRFHelper::validateCSRFToken('form_delete_inventory', $csrf_token)) {
                $repo = new LgpdInventoryRepository();
                $result = $repo->delete($id);
                
                if ($result) {
                    $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Inventário excluído com sucesso!</div>";
                } else {
                    $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: Inventário não excluído!</div>";
                }
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token de segurança inválido!</div>";
            }
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Método de requisição inválido!</div>";
        }
        
        header("Location: {$_ENV['URL_ADM']}lgpd-inventory");
        exit();
    }
}