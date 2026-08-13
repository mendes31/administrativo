<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\cashFlow\FinCashInvestmentRepository;

class DeleteFinCashInvestment
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (!isset($form['csrf_token'])
            || !CSRFHelper::validateCSRFToken('form_delete_fin_cash_investment', $form['csrf_token'])
            || !isset($form['id'])
        ) {
            GenerateLog::generateLog('error', 'Aplicação financeira não encontrada.', []);
            $_SESSION['error'] = 'Lançamento não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-investments');
            return;
        }

        $repo = new FinCashInvestmentRepository();
        $id = (int) $form['id'];
        $row = $repo->getById($id);
        if (!$row) {
            $_SESSION['error'] = 'Lançamento não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-investments');
            return;
        }

        if ($repo->delete($id)) {
            $_SESSION['success'] = 'Lançamento excluído.';
        } else {
            $_SESSION['error'] = 'Não foi possível excluir o lançamento.';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-investments');
    }
}
