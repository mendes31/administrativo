<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;

class DeleteInvCostPeriod
{
    public function index(): void
    {
        $redirect = $_ENV['URL_ADM'] . 'list-inventory-cost-periods';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        $token = (string)($form['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_delete_inv_cost_period', $token)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Requisição inválida. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $id = (int)($form['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período inválido.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new InvCostPeriodsRepository();
        $period = $repo->getOne($id);
        if ($period === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período não encontrado.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        if ((string)($period['status'] ?? '') === 'closed') {
            $_SESSION['msg'] = "<div class='alert alert-warning'>Período <strong>fechado</strong> não pode ser excluído (histórico preservado).</div>";
            header('Location: ' . $redirect);
            exit;
        }

        if ($repo->delete($id)) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Período excluído com DRE, critérios e vínculos do período.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Não foi possível excluir o período.</div>";
        }

        header('Location: ' . $redirect);
        exit;
    }
}
