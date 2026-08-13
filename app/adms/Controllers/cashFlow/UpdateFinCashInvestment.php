<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\cashFlow\FinCashAccountRepository;
use App\adms\Models\Repository\cashFlow\FinCashInvestmentRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateFinCashInvestment
{
    use FinCashInvestmentFormTrait;

    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new FinCashInvestmentRepository();

        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_fin_cash_investment', $this->data['form']['csrf_token'])
        ) {
            $this->save($repo);
            return;
        }

        $this->data['form'] = $repo->getById((int) $id);
        if (!$this->data['form']) {
            GenerateLog::generateLog('error', 'Aplicação financeira não encontrada', ['id' => (int) $id]);
            $_SESSION['error'] = 'Lançamento não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-investments');
            return;
        }
        $this->viewForm();
    }

    private function viewForm(): void
    {
        $accRepo = new FinCashAccountRepository();
        $invRepo = new FinCashInvestmentRepository();
        $this->data['account_options'] = $accRepo->getAll(true);
        $this->data['bank_suggestions'] = $invRepo->distinctBankLabels();
        $this->data['is_edit'] = true;

        $pageElements = [
            'title_head' => 'Editar Aplicação Financeira',
            'menu' => 'list-fin-cash-investments',
            'buttonPermission' => ['ListFinCashInvestments'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/cashFlow/investmentForm', $this->data);
        $loadView->loadView();
    }

    private function save(FinCashInvestmentRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        $id = (int) ($form['id'] ?? 0);
        $parsed = $this->validateInvestmentForm($form);
        if (!$parsed['ok']) {
            $_SESSION['error'] = implode(' ', $parsed['errors']);
            $this->data['form'] = array_merge($repo->getById($id) ?: [], $form);
            $this->viewForm();
            return;
        }
        if ($repo->update($id, $parsed['data'])) {
            $_SESSION['success'] = 'Lançamento atualizado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-investments');
            return;
        }
        $_SESSION['error'] = 'Não foi possível atualizar o lançamento.';
        $this->data['form'] = $repo->getById($id) ?: $form;
        $this->viewForm();
    }
}
