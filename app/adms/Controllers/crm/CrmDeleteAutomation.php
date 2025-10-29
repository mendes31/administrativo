<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmAutomationsRepository;

/**
 * Deletar Automação do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeleteAutomation
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da automação não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-automations");
            exit;
        }

        $repo = new CrmAutomationsRepository();
        $result = $repo->deleteAutomation((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Automação excluída com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir automação.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-list-automations");
        exit;
    }
}

