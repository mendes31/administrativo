<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdFinalidadesRepository;

if (!defined('C8L6K7E')) {
    header("Location: /");
    die("Erro: Página não encontrada<br>");
}

class LgpdFinalidadesDelete
{
    private array $data = [];
    private int $id;

    public function index(?int $id = null): void
    {
        $this->id = (int) $id;

        if (!empty($this->id)) {
            $this->deleteFinalidade();
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Finalidade não encontrada!</p>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-finalidades";
            header("Location: $urlRedirect");
            exit();
        }
    }

    private function deleteFinalidade(): void
    {
        $finalidadeRepository = new LgpdFinalidadesRepository();
        $result = $finalidadeRepository->delete($this->id);

        if ($result) {
            $_SESSION['msg'] = "<p class='alert-success'>Finalidade apagada com sucesso!</p>";
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Finalidade não foi apagada!</p>";
        }

        $urlRedirect = $_ENV['URL_ADM'] . "lgpd-finalidades";
        header("Location: $urlRedirect");
        exit();
    }
} 