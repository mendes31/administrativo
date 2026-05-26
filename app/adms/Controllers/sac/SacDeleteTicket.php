<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Models\Repository\SacTicketsRepository;

/**
 * Controller para excluir chamado do SAC
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacDeleteTicket
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do chamado não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        $ticketsRepo = new SacTicketsRepository();
        $result = $ticketsRepo->deleteTicket((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Chamado excluído com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir chamado.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
        exit;
    }
}
