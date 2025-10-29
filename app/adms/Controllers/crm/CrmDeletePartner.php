<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmPartnersRepository;

/**
 * Controller para deletar Parceiro CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeletePartner
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do parceiro não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
            exit;
        }

        $partnersRepo = new CrmPartnersRepository();
        $result = $partnersRepo->deletePartner((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Parceiro excluído com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir parceiro. Verifique se não há oportunidades vinculadas.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
        exit;
    }
}

