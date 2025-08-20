<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;

if (!defined('C8L6K7E')) {
    header("Location: /");
    die("Erro: Página não encontrada<br>");
}

class LgpdCategoriasTitularesDelete
{
    private array $data = [];
    private int $id;

    public function index(?int $id = null): void
    {
        $this->id = (int) $id;

        if (!empty($this->id)) {
            $this->deleteCategoriaTitular();
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Categoria de titular não encontrada!</p>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-categorias-titulares";
            header("Location: $urlRedirect");
            exit();
        }
    }

    private function deleteCategoriaTitular(): void
    {
        $categoriaTitularRepository = new LgpdCategoriasTitularesRepository();
        $result = $categoriaTitularRepository->delete($this->id);

        if ($result) {
            $_SESSION['msg'] = "<p class='alert-success'>Categoria de titular apagada com sucesso!</p>";
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Categoria de titular não foi apagada!</p>";
        }

        $urlRedirect = $_ENV['URL_ADM'] . "lgpd-categorias-titulares";
        header("Location: $urlRedirect");
        exit();
    }
} 