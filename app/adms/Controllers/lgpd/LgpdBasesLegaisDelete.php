<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdBasesLegaisRepository;

class LgpdBasesLegaisDelete
{
    public function index(): void
    {
        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $_SESSION['msg'] = "Erro: ID inválido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-bases-legais");
            exit;
        }

        $repository = new LgpdBasesLegaisRepository();
        $registro = $repository->getById($id);

        if (!$registro) {
            $_SESSION['msg'] = "Erro: Base Legal não encontrada!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-bases-legais");
            exit;
        }

        $result = $repository->delete($id);

        if ($result) {
            $_SESSION['msg'] = "Base Legal apagada com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro: Base Legal não foi apagada!";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-bases-legais");
        exit;
    }
} 