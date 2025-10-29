<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;

/**
 * Controller para deletar Oportunidade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeleteOpportunity
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da oportunidade não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $result = $opportunitiesRepo->deleteOpportunity((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Oportunidade excluída com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir oportunidade.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        exit;
    }
}

