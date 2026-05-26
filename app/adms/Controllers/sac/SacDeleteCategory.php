<?php

namespace App\adms\Controllers\sac;

use App\adms\Models\Repository\SacCategoriesRepository;

class SacDeleteCategory
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da categoria não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-categories");
            exit;
        }

        $categoriesRepo = new SacCategoriesRepository();
        $result = $categoriesRepo->deleteCategory((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Categoria excluída com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir categoria. Verifique se não há chamados vinculados.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "sac-list-categories");
        exit;
    }
}
