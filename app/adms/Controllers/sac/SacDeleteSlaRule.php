<?php

namespace App\adms\Controllers\sac;

use App\adms\Models\Repository\SacSlaRulesRepository;

class SacDeleteSlaRule
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da regra de SLA não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-sla-rules");
            exit;
        }

        $rulesRepo = new SacSlaRulesRepository();
        $result = $rulesRepo->deleteRule((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Regra de SLA excluída com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir regra de SLA. Verifique se não há chamados vinculados.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "sac-list-sla-rules");
        exit;
    }
}
