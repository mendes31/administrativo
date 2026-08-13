<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\cashFlow\FinCashAccountRepository;
use App\adms\Models\Repository\cashFlow\FinCashInvestmentRepository;
use App\adms\Views\Services\LoadViewService;

class CreateFinCashInvestment
{
    use FinCashInvestmentFormTrait;

    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_create_fin_cash_investment', $this->data['form']['csrf_token'])
        ) {
            $this->add();
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

        $pageElements = [
            'title_head' => 'Cadastrar Aplicação Financeira',
            'menu' => 'list-fin-cash-investments',
            'buttonPermission' => ['ListFinCashInvestments'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/cashFlow/investmentForm', $this->data);
        $loadView->loadView();
    }

    private function add(): void
    {
        $parsed = $this->validateInvestmentForm($this->data['form'] ?? []);
        if (!$parsed['ok']) {
            $_SESSION['error'] = implode(' ', $parsed['errors']);
            $this->viewForm();
            return;
        }
        $repo = new FinCashInvestmentRepository();
        $id = $repo->create($parsed['data']);
        if ($id) {
            $_SESSION['success'] = 'Lançamento de aplicação registrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-investments');
            return;
        }
        $_SESSION['error'] = 'Não foi possível gravar o lançamento.';
        $this->viewForm();
    }
}
