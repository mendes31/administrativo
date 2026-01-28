<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdTermosRepository;

class LgpdTermosDelete
{
    public function index(): void
    {
        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            $_SESSION['msg'] = "Erro: ID inválido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-termos");
            exit;
        }

        $repo = new LgpdTermosRepository();
        $registro = $repo->getById((int)$id);

        if (!$registro) {
            $_SESSION['msg'] = "Erro: Termo LGPD não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-termos");
            exit;
        }

        $result = $repo->delete((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Termo LGPD apagado com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro: Termo LGPD não foi apagado!";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-termos");
        exit;
    }
}


