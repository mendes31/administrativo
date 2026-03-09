<?php

namespace App\adms\Controllers\policies;

use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogAlteracaoService;

class DeletePolicy
{
    public function index(string $id = ''): void
    {
        $policyId = (int) ($id ?: ($_GET['id'] ?? 0));

        if ($policyId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Política inválida.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
            return;
        }

        $repo = new PoliciesRepository();
        $antes = $repo->getPolicyById($policyId) ?? [];

        try {
            $ok = $repo->deletePolicy($policyId);

            if ($ok) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
                LogAlteracaoService::registrarAlteracao(
                    'adms_policies',
                    $policyId,
                    $usuarioId,
                    'delete',
                    $antes,
                    []
                );

                $_SESSION['msg'] = '<div class="alert alert-success">Política excluída com sucesso.</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao excluir política.</div>';
            }
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao excluir política: ' . $e->getMessage() . '</div>';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
    }
}

