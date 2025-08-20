<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTiposDadosRepository;

if (!defined('C8L6K7E')) {
    header("Location: /");
    die("Erro: Página não encontrada<br>");
}

class LgpdTiposDadosDelete
{
    private array $data = [];
    private int $id;

    public function index(?int $id = null): void
    {
        $this->id = (int) $id;

        if (!empty($this->id)) {
            $this->deleteTipoDado();
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Tipo de dado não encontrado!</p>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-tipos-dados";
            header("Location: $urlRedirect");
            exit();
        }
    }

    private function deleteTipoDado(): void
    {
        $tipoDadoRepository = new LgpdTiposDadosRepository();
        $result = $tipoDadoRepository->delete($this->id);

        if ($result) {
            $_SESSION['msg'] = "<p class='alert-success'>Tipo de dado apagado com sucesso!</p>";
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Tipo de dado não foi apagado!</p>";
        }

        $urlRedirect = $_ENV['URL_ADM'] . "lgpd-tipos-dados";
        header("Location: $urlRedirect");
        exit();
    }
} 