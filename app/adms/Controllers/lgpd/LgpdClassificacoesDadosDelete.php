<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdClassificacoesDadosRepository;

if (!defined('C8L6K7E')) {
    header("Location: /");
    die("Erro: Página não encontrada<br>");
}

class LgpdClassificacoesDadosDelete
{
    private array $data = [];
    private int $id;

    public function index(?int $id = null): void
    {
        $this->id = (int) $id;

        if (!empty($this->id)) {
            $this->deleteClassificacao();
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Classificação não encontrada!</p>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-classificacoes-dados";
            header("Location: $urlRedirect");
            exit();
        }
    }

    private function deleteClassificacao(): void
    {
        $classificacaoRepository = new LgpdClassificacoesDadosRepository();
        $result = $classificacaoRepository->delete($this->id);

        if ($result) {
            $_SESSION['msg'] = "<p class='alert-success'>Classificação apagada com sucesso!</p>";
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Classificação não foi apagada!</p>";
        }

        $urlRedirect = $_ENV['URL_ADM'] . "lgpd-classificacoes-dados";
        header("Location: $urlRedirect");
        exit();
    }
} 