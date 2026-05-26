<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Models\Repository\SacClientsRepository;

class SacDeleteClient
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do cliente não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-clients");
            exit;
        }

        $repo = new SacClientsRepository();
        $result = $repo->deleteClient((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Cliente excluído com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir cliente. Verifique se não há chamados vinculados.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "sac-list-clients");
        exit;
    }
}
