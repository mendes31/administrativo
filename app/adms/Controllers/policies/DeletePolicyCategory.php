<?php

namespace App\adms\Controllers\policies;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogAlteracaoService;

class DeletePolicyCategory
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (
            !isset($form['csrf_token'])
            || !CSRFHelper::validateCSRFToken('form_delete_policy_category', $form['csrf_token'])
        ) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Requisição inválida.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
            return;
        }

        $id = (int) ($form['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
            return;
        }

        $repo = new PoliciesRepository();
        $old = $repo->getCategoriaById($id) ?? [];

        try {
            $ok = $repo->deleteCategoria($id);
            if ($ok) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
                LogAlteracaoService::registrarAlteracao(
                    'adms_policies_categorias',
                    $id,
                    $usuarioId,
                    'delete',
                    $old,
                    []
                );

                $_SESSION['msg'] = "<div class='alert alert-success'>Categoria de política excluída com sucesso.</div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao excluir categoria de política.</div>";
            }
        } catch (\Throwable $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao excluir categoria de política: " . $e->getMessage() . "</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
    }
}

